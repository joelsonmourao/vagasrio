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

export function buildJobPageTitle(job: JobSeoInput, settings?: SiteSettingsMap): string {
  if (job.seoTitle?.trim()) return job.seoTitle.trim();
  const suffix = settings?.[SETTING_KEYS.jobMetaSuffix] || siteConfig.name;
  return `${job.title} - ${job.city.name}/${job.state} | ${suffix}`;
}

export function buildJobPageDescription(job: JobSeoInput, settings?: SiteSettingsMap): string {
  if (job.seoDescription?.trim()) return job.seoDescription.trim();
  const plain = excerpt(job.description, 155);
  return plain || `Vaga de emprego em ${job.city.name}/${job.state}. Confira detalhes e candidate-se pelo Vagas RJ.`;
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

export function buildArticleSchema(
  article: {
    title: string;
    slug: string;
    excerpt: string;
    publishedAt: Date;
    updatedAt: Date;
    featuredImage?: string | null;
    category: { name: string };
  },
  settings?: SiteSettingsMap,
) {
  const publisherName = settings?.[SETTING_KEYS.siteName] || siteConfig.name;
  const logo = settings?.[SETTING_KEYS.logoPath] || '/assets/img/logo-vagas-rj.svg';
  const schema: Record<string, unknown> = {
    '@context': 'https://schema.org',
    '@type': 'BlogPosting',
    headline: article.title,
    description: article.excerpt,
    datePublished: formatSchemaDateTime(article.publishedAt),
    dateModified: formatSchemaDateTime(article.updatedAt),
    author: { '@type': 'Organization', name: publisherName },
    publisher: {
      '@type': 'Organization',
      name: publisherName,
      logo: {
        '@type': 'ImageObject',
        url: logo.startsWith('http') ? logo : baseUrl(logo, settings),
      },
    },
    mainEntityOfPage: baseUrl(`/blog/${article.slug}`, settings),
    url: baseUrl(`/blog/${article.slug}`, settings),
    articleSection: article.category.name,
    inLanguage: 'pt-BR',
  };
  const ogDefault = settings?.[SETTING_KEYS.ogImage] || '/assets/img/og-vagas-rj.png';
  if (article.featuredImage?.trim()) {
    const img = article.featuredImage.trim();
    schema.image = img.startsWith('http') ? img : baseUrl(img, settings);
  } else {
    schema.image = ogDefault.startsWith('http') ? ogDefault : baseUrl(ogDefault, settings);
  }
  return schema;
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
  const name = settings?.[SETTING_KEYS.siteName] || siteConfig.name;
  const url = resolvePublicBaseUrl(settings);
  return {
    '@context': 'https://schema.org',
    '@type': 'WebSite',
    name,
    url,
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
  const name = settings?.[SETTING_KEYS.siteName] || siteConfig.name;
  const url = resolvePublicBaseUrl(settings);
  const logo = settings?.[SETTING_KEYS.logoPath] || '/assets/img/logo-vagas-rj.svg';
  const ogImage = settings?.[SETTING_KEYS.ogImage] || '/assets/img/og-vagas-rj.png';
  return {
    '@context': 'https://schema.org',
    '@type': 'Organization',
    name,
    url,
    logo: logo.startsWith('http') ? logo : baseUrl(logo, settings),
    image: ogImage.startsWith('http') ? ogImage : baseUrl(ogImage, settings),
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
  const suffix = settings?.[SETTING_KEYS.siteName] || siteConfig.name;
  return `${post.title} | ${suffix}`;
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
  return 'index,follow';
}

export function buildCompanyPageTitle(companyName: string, settings?: SiteSettingsMap): string {
  const tpl = settings?.[SETTING_KEYS.companyMetaTemplate] || 'Vagas na {empresa} - Rio de Janeiro | Vagas RJ';
  return tpl.replace(/\{empresa\}/gi, companyName);
}

export function buildCityPageTitle(cityName: string, settings?: SiteSettingsMap): string {
  const tpl = settings?.[SETTING_KEYS.cityMetaTemplate] || 'Vagas em {cidade} RJ - Vagas RJ';
  return tpl.replace(/\{cidade\}/gi, cityName);
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
