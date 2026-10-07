import { absoluteUrl, baseUrl, resolvePublicBaseUrl, siteConfig } from './config';
import { isRealCompanyLogo, sanitizeSchemaUrl } from './public-content';
import { formatSchemaDateTime } from './datetime-brazil';
import type { SiteSettingsMap } from './site-settings';
import { excerpt } from './format';
import { SETTING_KEYS } from './site-settings';
import { blogPostCanBeIndexed } from './blog-indexing';

type JobSeoInput = {
  title: string;
  slug: string;
  description: string;
  seoTitle?: string | null;
  seoDescription?: string | null;
  canonicalUrl?: string | null;
  isIndexable?: boolean;
  city: { name: string };
  state: string;
};

function brandName(settings?: SiteSettingsMap): string {
  return settings?.[SETTING_KEYS.siteName]?.trim() || siteConfig.name;
}

/** Padrão dos grandes portais: Cargo - Cidade/UF | Marca */
export function buildJobPageTitle(job: JobSeoInput, settings?: SiteSettingsMap): string {
  if (job.seoTitle?.trim()) return job.seoTitle.trim();
  return `${job.title} em ${job.city.name}/${job.state} | ${brandName(settings)}`;
}

export function buildJobPageDescription(job: JobSeoInput, settings?: SiteSettingsMap): string {
  if (job.seoDescription?.trim()) return job.seoDescription.trim();
  const plain = excerpt(job.description, 120);
  const brand = brandName(settings);
  return (
    plain ||
    `Vaga de emprego: ${job.title} em ${job.city.name}/${job.state}. Confira requisitos e candidate-se pelo ${brand}.`
  );
}

export function jobCanonical(job: JobSeoInput, settings?: SiteSettingsMap): string {
  if (job.canonicalUrl?.trim()) return absoluteUrl(job.canonicalUrl.trim(), settings);
  return baseUrl(`/vagas/${job.slug}`, settings);
}

export function jobRobotsMeta(
  job: JobSeoInput,
  indexingOn: boolean,
  publiclyVisible = true,
): string {
  if (!publiclyVisible || !indexingOn || job.isIndexable === false) return 'noindex,follow';
  return 'index,follow';
}

/**
 * Schema para Discover/News-ready: BlogPosting (com author completo).
 * Não misturar com microdata itemscope no HTML — o Google conta como 2 artigos.
 */
export function buildArticleSchema(
  article: {
    title: string;
    slug: string;
    excerpt: string;
    content?: string;
    publishedAt: Date;
    updatedAt: Date;
    featuredImage?: string | null;
    category: { name: string };
  },
  settings?: SiteSettingsMap,
) {
  const publisherName = brandName(settings);
  const logo = settings?.[SETTING_KEYS.logoPath] || '/assets/img/logo-vagas-rj.svg';
  const ogDefault = settings?.[SETTING_KEYS.ogImage] || '/assets/img/og-vagas-rj.png';
  const pageUrl = baseUrl(`/blog/${article.slug}`, settings);
  const aboutUrl = baseUrl('/sobre', settings);
  const logoUrl = logo.startsWith('http') ? logo : baseUrl(logo, settings);
  let imageUrl = ogDefault.startsWith('http') ? ogDefault : baseUrl(ogDefault, settings);
  if (article.featuredImage?.trim()) {
    const img = article.featuredImage.trim();
    imageUrl = img.startsWith('http') ? img : baseUrl(img, settings);
  }

  const plainBody = article.content ? htmlToPlainText(article.content) : '';
  const wordCount = plainBody ? plainBody.split(/\s+/).filter(Boolean).length : undefined;

  const imageObject = {
    '@type': 'ImageObject',
    url: imageUrl,
    width: 1200,
    height: 675,
  };

  const publisher = {
    '@type': 'Organization',
    name: publisherName,
    url: resolvePublicBaseUrl(settings),
    logo: {
      '@type': 'ImageObject',
      url: logoUrl,
      width: 600,
      height: 60,
    },
  };

  return {
    '@context': 'https://schema.org',
    '@type': 'BlogPosting',
    headline: article.title.slice(0, 110),
    alternativeHeadline: article.excerpt.slice(0, 110),
    description: article.excerpt,
    datePublished: formatSchemaDateTime(article.publishedAt),
    dateModified: formatSchemaDateTime(article.updatedAt),
    author: {
      '@type': 'Organization',
      name: `Redação ${publisherName}`,
      url: aboutUrl,
    },
    publisher,
    mainEntityOfPage: {
      '@type': 'WebPage',
      '@id': pageUrl,
      url: pageUrl,
      name: article.title,
      isPartOf: {
        '@type': 'WebSite',
        name: publisherName,
        url: resolvePublicBaseUrl(settings),
      },
    },
    url: pageUrl,
    image: [imageObject],
    thumbnailUrl: imageUrl,
    articleSection: article.category.name,
    keywords: [article.category.name, 'emprego RJ', 'vagas Rio de Janeiro', publisherName].join(', '),
    about: [
      {
        '@type': 'Thing',
        name: 'Emprego no Rio de Janeiro',
      },
      {
        '@type': 'Thing',
        name: article.category.name,
      },
    ],
    inLanguage: 'pt-BR',
    isAccessibleForFree: true,
    ...(wordCount ? { wordCount } : {}),
    ...(plainBody ? { articleBody: plainBody.slice(0, 8000) } : {}),
    speakable: {
      '@type': 'SpeakableSpecification',
      cssSelector: ['.article-header h1', '.article-body p'],
    },
  };
}

function htmlToPlainText(html: string): string {
  return String(html)
    .replace(/<script[\s\S]*?<\/script>/gi, ' ')
    .replace(/<style[\s\S]*?<\/style>/gi, ' ')
    .replace(/<br\s*\/?>/gi, '\n')
    .replace(/<\/(p|div|h[1-6]|li|tr)>/gi, '\n')
    .replace(/<[^>]+>/g, ' ')
    .replace(/&nbsp;/g, ' ')
    .replace(/&amp;/g, '&')
    .replace(/&quot;/g, '"')
    .replace(/&#39;/g, "'")
    .replace(/&lt;/g, '<')
    .replace(/&gt;/g, '>')
    .replace(/[ \t]+\n/g, '\n')
    .replace(/\n{3,}/g, '\n\n')
    .replace(/[ \t]{2,}/g, ' ')
    .trim();
}

function decodeBasicEntities(text: string): string {
  return text
    .replace(/&nbsp;/g, ' ')
    .replace(/&amp;/g, '&')
    .replace(/&quot;/g, '"')
    .replace(/&#39;/g, "'")
    .replace(/&lt;/g, '<')
    .replace(/&gt;/g, '>')
    .replace(/\s+/g, ' ')
    .trim();
}

/** Extrai pares pergunta/resposta da seção “Perguntas frequentes” (h3 + p). */
export function extractFaqPairsFromHtml(html: string): { question: string; answer: string }[] {
  if (!html) return [];
  const faqMatch = html.match(
    /<h2[^>]*>\s*Perguntas frequentes\s*<\/h2>([\s\S]*?)(?=<h2\b|$)/i,
  );
  if (!faqMatch) return [];
  const block = faqMatch[1];
  const pairs: { question: string; answer: string }[] = [];
  const re = /<h3[^>]*>([\s\S]*?)<\/h3>\s*<p[^>]*>([\s\S]*?)<\/p>/gi;
  let m: RegExpExecArray | null;
  while ((m = re.exec(block)) !== null) {
    const question = decodeBasicEntities(m[1].replace(/<[^>]+>/g, ''));
    const answer = decodeBasicEntities(m[2].replace(/<[^>]+>/g, ''));
    if (question.length >= 8 && answer.length >= 12) {
      pairs.push({ question, answer });
    }
  }
  return pairs.slice(0, 8);
}

export function buildFaqPageSchema(
  pairs: { question: string; answer: string }[],
  pageUrl: string,
): Record<string, unknown> | undefined {
  if (pairs.length < 2) return undefined;
  return {
    '@context': 'https://schema.org',
    '@type': 'FAQPage',
    mainEntity: pairs.map((pair) => ({
      '@type': 'Question',
      name: pair.question,
      acceptedAnswer: {
        '@type': 'Answer',
        text: pair.answer,
      },
    })),
    url: pageUrl,
  };
}

/** Extrai passos do checklist editorial para HowTo (rich result). */
export function extractHowToStepsFromHtml(html: string): string[] {
  if (!html) return [];
  const sectionMatch = html.match(
    /<h2[^>]*>\s*(Checklist antes de avançar|Passo a passo prático)\s*<\/h2>([\s\S]*?)(?=<h2\b|$)/i,
  );
  if (!sectionMatch) return [];
  const block = sectionMatch[2];
  const steps: string[] = [];
  const re = /<li[^>]*>([\s\S]*?)<\/li>/gi;
  let m: RegExpExecArray | null;
  while ((m = re.exec(block)) !== null) {
    const text = decodeBasicEntities(m[1].replace(/<[^>]+>/g, ''));
    if (text.length >= 12) steps.push(text);
  }
  return steps.slice(0, 12);
}

export function buildHowToSchema(
  name: string,
  description: string,
  steps: string[],
  pageUrl: string,
  imageUrl?: string,
): Record<string, unknown> | undefined {
  if (steps.length < 3) return undefined;
  return {
    '@context': 'https://schema.org',
    '@type': 'HowTo',
    name: name.slice(0, 110),
    description: description.slice(0, 300),
    url: pageUrl,
    ...(imageUrl ? { image: imageUrl } : {}),
    totalTime: 'PT15M',
    inLanguage: 'pt-BR',
    step: steps.map((text, index) => ({
      '@type': 'HowToStep',
      position: index + 1,
      name: `Passo ${index + 1}`,
      text,
      url: `${pageUrl}#passo-${index + 1}`,
    })),
  };
}

export function buildRelatedPostsItemListSchema(
  posts: { title: string; slug: string }[],
  settings?: SiteSettingsMap,
): Record<string, unknown> | undefined {
  if (!posts.length) return undefined;
  return {
    '@context': 'https://schema.org',
    '@type': 'ItemList',
    name: 'Leia também',
    itemListOrder: 'https://schema.org/ItemListOrderAscending',
    numberOfItems: posts.length,
    itemListElement: posts.map((post, index) => ({
      '@type': 'ListItem',
      position: index + 1,
      name: post.title,
      url: baseUrl(`/blog/${post.slug}`, settings),
    })),
  };
}

export function buildBreadcrumbSchema(
  items: { name: string; path: string }[],
  settings?: SiteSettingsMap,
) {
  return {
    '@context': 'https://schema.org',
    '@type': 'BreadcrumbList',
    itemListElement: items.map((item, index) => ({
      '@type': 'ListItem',
      position: index + 1,
      name: item.name,
      item: baseUrl(item.path, settings),
    })),
  };
}

export function mergeJsonLd(
  ...schemas: (Record<string, unknown> | Record<string, unknown>[] | undefined)[]
): Record<string, unknown>[] {
  const out: Record<string, unknown>[] = [];
  for (const s of schemas) {
    if (!s) continue;
    if (Array.isArray(s)) out.push(...s);
    else out.push(s);
  }
  return out;
}

export function buildWebSiteSchema(settings?: SiteSettingsMap) {
  const name = brandName(settings);
  const url = resolvePublicBaseUrl(settings);
  return {
    '@context': 'https://schema.org',
    '@type': 'WebSite',
    name,
    alternateName: ['Vagas RJ', 'Vagas Rio', 'Empregos RJ', 'Vagas RJ RIO'].filter(
      (alt, i, arr) => alt !== name && arr.indexOf(alt) === i,
    ),
    url,
    description: `Portal de vagas de emprego no Rio de Janeiro (RJ). Busque por cidade, empresa e categoria no ${name}.`,
    inLanguage: 'pt-BR',
    potentialAction: {
      '@type': 'SearchAction',
      target: {
        '@type': 'EntryPoint',
        urlTemplate: `${url}/vagas?q={search_term_string}`,
      },
      'query-input': 'required name=search_term_string',
    },
  };
}

export function buildPublisherOrganizationSchema(settings?: SiteSettingsMap) {
  const name = brandName(settings);
  const url = resolvePublicBaseUrl(settings);
  const logo = settings?.[SETTING_KEYS.logoPath] || '/assets/img/logo-vagas-rj.svg';
  const ogImage = settings?.[SETTING_KEYS.ogImage] || '/assets/img/og-vagas-rj.png';
  return {
    '@context': 'https://schema.org',
    '@type': 'Organization',
    name,
    alternateName: name === 'Vagas RJ RIO' ? ['Vagas RJ', 'Vagas Rio'] : 'Vagas RJ',
    url,
    logo: logo.startsWith('http') ? logo : baseUrl(logo, settings),
    image: ogImage.startsWith('http') ? ogImage : baseUrl(ogImage, settings),
    areaServed: {
      '@type': 'AdministrativeArea',
      name: 'Rio de Janeiro',
    },
    contactPoint: {
      '@type': 'ContactPoint',
      contactType: 'customer support',
      email: settings?.[SETTING_KEYS.contactEmail] || siteConfig.contactEmail,
      availableLanguage: 'Portuguese',
    },
  };
}

type BlogSeoInput = {
  title: string;
  slug: string;
  excerpt: string;
  seoTitle?: string | null;
  seoDescription?: string | null;
  canonicalUrl?: string | null;
  isIndexable?: boolean;
};

export function buildBlogPageTitle(post: BlogSeoInput, settings?: SiteSettingsMap): string {
  if (post.seoTitle?.trim()) return post.seoTitle.trim();
  return `${post.title} | Dicas de Emprego | ${brandName(settings)}`;
}

export function buildBlogPageDescription(post: BlogSeoInput): string {
  if (post.seoDescription?.trim()) return post.seoDescription.trim();
  return excerpt(post.excerpt, 155);
}

export function blogCanonical(post: BlogSeoInput, settings?: SiteSettingsMap): string {
  if (post.canonicalUrl?.trim()) return absoluteUrl(post.canonicalUrl.trim(), settings);
  return baseUrl(`/blog/${post.slug}`, settings);
}

export function blogRobotsMeta(post: BlogSeoInput, indexingOn: boolean): string {
  if (!blogPostCanBeIndexed(post, indexingOn)) return 'noindex,follow';
  return 'index,follow,max-image-preview:large,max-snippet:-1,max-video-preview:-1';
}

export function buildCompanyPageTitle(companyName: string, settings?: SiteSettingsMap): string {
  const tpl =
    settings?.[SETTING_KEYS.companyMetaTemplate] ||
    `Vagas na {empresa} no Rio de Janeiro | ${brandName(settings)}`;
  return tpl.replace(/\{empresa\}/gi, companyName);
}

export function buildCityPageTitle(cityName: string, settings?: SiteSettingsMap): string {
  const tpl =
    settings?.[SETTING_KEYS.cityMetaTemplate] ||
    `Vagas em {cidade} RJ | Empregos | ${brandName(settings)}`;
  return tpl.replace(/\{cidade\}/gi, cityName);
}

export function buildHomeSeoDefaults(settings?: SiteSettingsMap): { title: string; description: string } {
  const brand = brandName(settings);
  return {
    title:
      settings?.[SETTING_KEYS.homeSeoTitle]?.trim() ||
      `Vagas de emprego no Rio de Janeiro | Empregos RJ | ${brand}`,
    description:
      settings?.[SETTING_KEYS.homeSeoDescription]?.trim() ||
      `Vagas de emprego no Rio de Janeiro (RJ). Busque empregos Rio, vagas RJ e vagas rio por cidade, cargo e empresa no ${brand}. Grátis para candidatos.`,
  };
}

/**
 * Título + meta no padrão dos grandes portais (ex.: “430 vagas… Cargo · Cargo · Cargo”).
 * Ajuda o snippet do Google para consultas como “vagas rio de janeiro”.
 */
export function buildJobsListingSeo(opts: {
  total: number;
  jobTitles?: string[];
  placeLabel?: string;
  brand?: string;
  settings?: SiteSettingsMap;
}): { title: string; description: string } {
  const brand = opts.brand || brandName(opts.settings);
  const place = opts.placeLabel || 'Rio de Janeiro';
  const total = Math.max(0, opts.total || 0);
  const titles = (opts.jobTitles || [])
    .map((t) => t.replace(/\s+/g, ' ').trim())
    .filter(Boolean)
    .slice(0, 5);

  const title =
    total > 0
      ? `Vagas de emprego em ${place} | ${total} vagas | ${brand}`
      : `Vagas de emprego em ${place} | Empregos RJ | ${brand}`;

  const head =
    total > 0
      ? `${total} vaga${total === 1 ? '' : 's'} de emprego para ${place}.`
      : `Vagas de emprego para ${place}.`;
  const sample = titles.length ? ` ${titles.join(' · ')}.` : '';
  const tail = ` Empregos RJ, vagas rio e vagas RJ atualizadas no ${brand}.`;
  let description = `${head}${sample}${tail}`.replace(/\s+/g, ' ').trim();
  if (description.length > 160) {
    description = `${description.slice(0, 157).trim()}...`;
  }

  return { title, description };
}

export function buildOrganizationSchema(
  company: {
    name: string;
    slug: string;
    description?: string | null;
    website?: string | null;
    logo?: string | null;
  },
  settings?: SiteSettingsMap,
) {
  const schema: Record<string, unknown> = {
    '@context': 'https://schema.org',
    '@type': 'Organization',
    name: company.name,
    url: baseUrl(`/empresas/${company.slug}`, settings),
  };
  if (company.description) schema.description = company.description;
  const companyWebsite = sanitizeSchemaUrl(company.website);
  if (companyWebsite) schema.sameAs = companyWebsite;
  if (company.logo && isRealCompanyLogo(company.logo)) {
    const logo = company.logo.trim();
    schema.logo = logo.startsWith('http') ? logo : baseUrl(logo, settings);
  }
  return schema;
}
