import { absoluteUrl, baseUrl, resolvePublicBaseUrl, siteConfig } from './config';
import { isRealCompanyLogo, sanitizeSchemaUrl } from './public-content';
import { formatSchemaDateTime } from './datetime-brazil';
import type { SiteSettingsMap } from './site-settings';
import { excerpt } from './format';
import { SETTING_KEYS } from './site-settings';

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
 * Schema para Discover/News-ready: Article + BlogPosting.
 * Google Discover valoriza imagem grande, data clara e conteúdo original.
 * Google News exige inscrição no Publisher Center — o schema ajuda, mas não garante inclusão.
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
  const logoUrl = logo.startsWith('http') ? logo : baseUrl(logo, settings);
  let imageUrl = ogDefault.startsWith('http') ? ogDefault : baseUrl(ogDefault, settings);
  if (article.featuredImage?.trim()) {
    const img = article.featuredImage.trim();
    imageUrl = img.startsWith('http') ? img : baseUrl(img, settings);
  }

  const imageObject = {
    '@type': 'ImageObject',
    url: imageUrl,
    width: 1200,
    height: 675,
  };

  return {
    '@context': 'https://schema.org',
    '@type': ['BlogPosting', 'Article'],
    headline: article.title.slice(0, 110),
    alternativeHeadline: article.excerpt.slice(0, 110),
    description: article.excerpt,
    datePublished: formatSchemaDateTime(article.publishedAt),
    dateModified: formatSchemaDateTime(article.updatedAt),
    author: {
      '@type': 'Person',
      name: `Redação ${publisherName}`,
      url: baseUrl('/sobre', settings),
    },
    publisher: {
      '@type': 'Organization',
      name: publisherName,
      logo: {
        '@type': 'ImageObject',
        url: logoUrl,
        width: 600,
        height: 60,
      },
    },
    mainEntityOfPage: {
      '@type': 'WebPage',
      '@id': pageUrl,
    },
    url: pageUrl,
    image: [imageObject],
    articleSection: article.category.name,
    inLanguage: 'pt-BR',
    isAccessibleForFree: true,
    speakable: {
      '@type': 'SpeakableSpecification',
      cssSelector: ['.article-header h1', '.article-body p'],
    },
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
    alternateName: ['Vagas RJ', 'Vagas Rio', 'Empregos RJ'],
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
    alternateName: 'Vagas RJ',
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
  if (!indexingOn || post.isIndexable === false) return 'noindex,follow';
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
    title: settings?.[SETTING_KEYS.homeSeoTitle]?.trim() || `Vagas de Emprego no Rio de Janeiro | ${brand}`,
    description:
      settings?.[SETTING_KEYS.homeSeoDescription]?.trim() ||
      `Encontre vagas de emprego no Rio de Janeiro. Busque por cidade, empresa e categoria no ${brand}. Grátis para candidatos.`,
  };
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
