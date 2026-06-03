import type { SiteSettingsMap } from './site-settings';
import { SETTING_KEYS } from './site-settings';

export const siteConfig = {
  name: process.env.SITE_NAME || 'Vagas RJ',
  subtitle: 'Empregos no Rio de Janeiro',
  mainUf: 'RJ',
  mainStateName: 'Rio de Janeiro',
  contactEmail: process.env.SITE_CONTACT_EMAIL || 'contato@vagasrj.rio.br',
  perPage: 12,
  allowedCities: [
    'Rio de Janeiro',
    'Niterói',
    'São Gonçalo',
    'Duque de Caxias',
    'Nova Iguaçu',
    'Petrópolis',
    'Volta Redonda',
    'Campos dos Goytacazes',
    'Cabo Frio',
    'Macaé',
    'Itaboraí',
    'Belford Roxo',
  ],
  cityPostalCodes: {
    'Rio de Janeiro': '20040-020',
    'Niterói': '24020-041',
    'São Gonçalo': '24440-000',
    'Duque de Caxias': '25020-010',
    'Nova Iguaçu': '26220-010',
    'Petrópolis': '25610-010',
    'Volta Redonda': '27253-000',
    'Campos dos Goytacazes': '28010-010',
    'Cabo Frio': '28905-000',
    'Macaé': '27910-010',
    'Itaboraí': '24800-000',
    'Belford Roxo': '26113-010',
  } as Record<string, string>,
};

/** URL pública absoluta (env SITE_BASE_URL ou painel; nunca path relativo). */
export function resolvePublicBaseUrl(settings?: SiteSettingsMap): string {
  const fromPanel = settings?.[SETTING_KEYS.siteBaseUrl]?.trim();
  const fromEnv = (process.env.SITE_BASE_URL || '').trim();
  let base = (fromPanel || fromEnv || 'http://localhost:4321').replace(/\/$/, '');

  if (process.env.NODE_ENV === 'production' && /localhost|127\.0\.0\.1/i.test(base)) {
    if (fromEnv && !/localhost|127\.0\.0\.1/i.test(fromEnv)) {
      base = fromEnv.replace(/\/$/, '');
    }
  }

  return base;
}

/** @deprecated use resolvePublicBaseUrl — mantido para compatibilidade */
export function getEnvBaseUrl(): string {
  return resolvePublicBaseUrl();
}

export const siteConfigLegacyBaseUrl = resolvePublicBaseUrl();

export function baseUrl(path = '', settings?: SiteSettingsMap): string {
  const base = resolvePublicBaseUrl(settings);
  const p = path.startsWith('/') ? path : path ? `/${path}` : '';
  return `${base}${p}`;
}

/** Garante URL absoluta (canonical, OG, schema). */
export function absoluteUrl(href: string, settings?: SiteSettingsMap): string {
  if (/^https?:\/\//i.test(href)) return href;
  const path = href.startsWith('/') ? href : `/${href}`;
  return baseUrl(path, settings);
}
