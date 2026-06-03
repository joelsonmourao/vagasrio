import { prisma } from './db';
import { siteConfig } from './config';

export const SETTING_KEYS = {
  siteName: 'site.name',
  siteSubtitle: 'site.subtitle',
  homeSeoTitle: 'seo.home.title',
  homeSeoDescription: 'seo.home.description',
  siteBaseUrl: 'site.base_url',
  contactEmail: 'site.contact_email',
  ogImage: 'seo.og_image',
  logoPath: 'site.logo_path',
  faviconPath: 'site.favicon_path',
  jobMetaSuffix: 'seo.job.suffix',
  companyMetaTemplate: 'seo.company.template',
  cityMetaTemplate: 'seo.city.template',
  gscVerification: 'seo.google_site_verification',
  gaCode: 'seo.google_analytics',
  adsenseClient: 'seo.adsense_client',
  adsenseEnabled: 'seo.adsense_enabled',
  adsenseScript: 'seo.adsense_script',
  adsTxt: 'seo.ads_txt',
  indexingEnabled: 'seo.indexing_enabled',
  robotsExtra: 'seo.robots_extra',
  sitemapNote: 'seo.sitemap_enabled',
} as const;

export type SiteSettingsMap = Record<string, string>;

function envDefaults(): SiteSettingsMap {
  return {
    [SETTING_KEYS.siteName]: siteConfig.name,
    [SETTING_KEYS.siteSubtitle]: siteConfig.subtitle,
    [SETTING_KEYS.homeSeoTitle]: `${siteConfig.name} - Empregos no Rio de Janeiro`,
    [SETTING_KEYS.homeSeoDescription]:
      'Encontre vagas de emprego no Rio de Janeiro (RJ) por cidade, empresa e categoria.',
    [SETTING_KEYS.siteBaseUrl]: (process.env.SITE_BASE_URL || 'http://localhost:4321').replace(/\/$/, ''),
    [SETTING_KEYS.contactEmail]: siteConfig.contactEmail,
    [SETTING_KEYS.ogImage]: '/assets/img/og-vagas-rj.png',
    [SETTING_KEYS.logoPath]: '/assets/img/logo-vagas-rj.svg',
    [SETTING_KEYS.faviconPath]: '/favicon.svg',
    [SETTING_KEYS.jobMetaSuffix]: 'Vagas RJ',
    [SETTING_KEYS.companyMetaTemplate]: 'Vagas na {empresa} - Rio de Janeiro | Vagas RJ',
    [SETTING_KEYS.cityMetaTemplate]: 'Vagas em {cidade} RJ - Vagas RJ',
    [SETTING_KEYS.gscVerification]: '',
    [SETTING_KEYS.gaCode]: '',
    [SETTING_KEYS.adsenseClient]: process.env.ADSENSE_CLIENT_ID || '',
    [SETTING_KEYS.adsenseEnabled]: '0',
    [SETTING_KEYS.adsenseScript]: '',
    [SETTING_KEYS.adsTxt]:
      process.env.ADSENSE_ADS_TXT ||
      'google.com, pub-0000000000000000, DIRECT, f08c47fec0942fa0',
    [SETTING_KEYS.indexingEnabled]: '1',
    [SETTING_KEYS.robotsExtra]: '',
    [SETTING_KEYS.sitemapNote]: '1',
  };
}

export async function getSiteSettings(): Promise<SiteSettingsMap> {
  const defaults = envDefaults();
  const rows = await prisma.siteSetting.findMany();
  const map = { ...defaults };
  for (const row of rows) {
    if (row.value !== '') map[row.key] = row.value;
  }
  return map;
}

/** Não quebra sitemap/SEO se o banco ou tabela site_settings estiver indisponível. */
export async function getSiteSettingsSafe(): Promise<SiteSettingsMap> {
  try {
    return await getSiteSettings();
  } catch {
    return envDefaults();
  }
}

export function isSitemapEnabled(settings: SiteSettingsMap): boolean {
  return settings[SETTING_KEYS.sitemapNote] !== '0';
}

export async function saveSiteSettings(data: SiteSettingsMap): Promise<void> {
  for (const [key, value] of Object.entries(data)) {
    await prisma.siteSetting.upsert({
      where: { key },
      update: { value: String(value ?? '') },
      create: { key, value: String(value ?? '') },
    });
  }
}

export function isIndexingEnabled(settings: SiteSettingsMap): boolean {
  return settings[SETTING_KEYS.indexingEnabled] !== '0';
}

export function isAdsenseEnabled(settings: SiteSettingsMap): boolean {
  return settings[SETTING_KEYS.adsenseEnabled] === '1';
}
