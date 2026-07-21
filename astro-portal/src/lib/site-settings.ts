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

/** Troca marcas legadas pela marca atual, sem duplicar "RIO". */
export function upgradeBrandText(text: string, brand = siteConfig.name): string {
  if (!text) return text;
  return text
    .replace(/Vagas RJ RIO/g, '\u0000BRAND\u0000')
    .replace(/Empregos no Rio de Janeiro - Rio Vagas/gi, brand)
    .replace(/Rio Vagas/gi, brand)
    .replace(/Vagas RJ/g, brand)
    .replace(/Vagas Rio(?!\s+de\s+Janeiro)/gi, brand)
    .replace(/\u0000BRAND\u0000/g, brand);
}

function envDefaults(): SiteSettingsMap {
  const brand = siteConfig.name;
  return {
    [SETTING_KEYS.siteName]: brand,
    [SETTING_KEYS.siteSubtitle]: siteConfig.subtitle,
    [SETTING_KEYS.homeSeoTitle]: `Vagas de emprego no Rio de Janeiro | Empregos RJ | ${brand}`,
    [SETTING_KEYS.homeSeoDescription]:
      `Vagas de emprego no Rio de Janeiro (RJ). Busque empregos Rio, vagas RJ e vagas rio por cidade, cargo e empresa no ${brand}. Grátis para candidatos.`,
    [SETTING_KEYS.siteBaseUrl]: (process.env.SITE_BASE_URL || 'http://localhost:4321').replace(/\/$/, ''),
    [SETTING_KEYS.contactEmail]: siteConfig.contactEmail,
    [SETTING_KEYS.ogImage]: '/assets/img/og-vagas-rj.png',
    [SETTING_KEYS.logoPath]: '/assets/img/logo-vagas-rj.svg',
    [SETTING_KEYS.faviconPath]: '/favicon.svg',
    [SETTING_KEYS.jobMetaSuffix]: brand,
    [SETTING_KEYS.companyMetaTemplate]: `Vagas na {empresa} - Rio de Janeiro | ${brand}`,
    [SETTING_KEYS.cityMetaTemplate]: `Vagas de emprego em {cidade} RJ | Empregos | ${brand}`,
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

const BRAND_SETTING_KEYS = [
  SETTING_KEYS.siteName,
  SETTING_KEYS.homeSeoTitle,
  SETTING_KEYS.homeSeoDescription,
  SETTING_KEYS.jobMetaSuffix,
  SETTING_KEYS.companyMetaTemplate,
  SETTING_KEYS.cityMetaTemplate,
] as const;

export async function getSiteSettings(): Promise<SiteSettingsMap> {
  const defaults = envDefaults();
  const rows = await prisma.siteSetting.findMany();
  const map = { ...defaults };
  for (const row of rows) {
    if (row.value !== '') map[row.key] = row.value;
  }

  // SITE_NAME no ambiente (Coolify) prevalece sobre valor antigo no banco
  const envName = (process.env.SITE_NAME || '').trim();
  if (envName) map[SETTING_KEYS.siteName] = envName;

  for (const key of BRAND_SETTING_KEYS) {
    if (map[key]) map[key] = upgradeBrandText(map[key]);
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
