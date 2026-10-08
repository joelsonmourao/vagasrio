import { absoluteUrl, baseUrl, siteConfig } from './config';
import { formatJobPostingDate, formatJobPostingValidThrough } from './datetime-brazil';
import { parseJobSalaryAmount } from './format';
import { isValidApplyChannel, parseApplyChannel } from './apply-channel';
import { isRealCompanyLogo, sanitizeSchemaUrl } from './public-content';
import type { SiteSettingsMap } from './site-settings';

const STREET_ADDRESS_FALLBACK = 'Não informado';

type JobForSchema = {
  id: number;
  title: string;
  slug: string;
  description: string;
  publishedAt: Date;
  validThrough: Date | null;
  applyUrl: string | null;
  employmentType: string | null;
  salary: string | null;
  company: { name: string; website: string | null; logo: string | null };
  city: { name: string };
};

/**
 * Preserva apenas a marcação HTML aceita pelo Google em JobPosting.
 * Remove scripts, estilos, atributos e tags que não ajudam a leitura.
 */
export function sanitizeJobDescriptionHtml(html: string): string {
  return html
    .replace(/<script[\s\S]*?<\/script>/gi, ' ')
    .replace(/<style[\s\S]*?<\/style>/gi, ' ')
    .replace(/<!--[\s\S]*?-->/g, ' ')
    .replace(/<(\/?)(p|ul|li)\b[^>]*>/gi, '<$1$2>')
    .replace(/<br\b[^>]*\/?>/gi, '<br>')
    .replace(/<(?!\/?(?:p|ul|li)\b|br\b)[^>]+>/gi, ' ')
    .replace(/\s+/g, ' ')
    .replace(/\s+<\/(p|li)>/gi, '</$1>')
    .trim();
}

function normalizeEmploymentType(value: string | null): string | null {
  if (!value) return null;
  const v = value.toUpperCase();
  if (['FULL_TIME', 'PART_TIME', 'CONTRACTOR', 'TEMPORARY', 'INTERN', 'VOLUNTEER', 'PER_DIEM', 'OTHER'].includes(v)) {
    return v;
  }
  const map: Record<string, string> = {
    clt: 'FULL_TIME',
    'tempo integral': 'FULL_TIME',
    'meio periodo': 'PART_TIME',
    estagio: 'INTERN',
  };
  return map[value.toLowerCase()] ?? null;
}

/** Endereço completo da vaga (rua/número). Hoje não há campo no banco. */
export function resolveJobStreetAddress(_job: JobForSchema): string | null {
  return null;
}

export function formatJobStreetAddressDisplay(job: JobForSchema): string {
  return resolveJobStreetAddress(job) ?? STREET_ADDRESS_FALLBACK;
}

function resolveOrganizationLogoUrl(
  companyLogo: string | null | undefined,
  settings?: SiteSettingsMap,
): string | null {
  if (!companyLogo || !isRealCompanyLogo(companyLogo)) return null;
  const raw = sanitizeSchemaUrl(companyLogo) || companyLogo.trim();
  if (raw.startsWith('http://') || raw.startsWith('https://')) return raw;
  const path = raw.startsWith('/') ? raw : `/${raw}`;
  return absoluteUrl(path, settings);
}

function buildPostalAddress(job: JobForSchema): Record<string, string> {
  const postalCode = siteConfig.cityPostalCodes[job.city.name];
  const address: Record<string, string> = {
    '@type': 'PostalAddress',
    addressLocality: job.city.name,
    addressRegion: siteConfig.mainUf,
    addressCountry: 'BR',
  };
  const streetAddress = resolveJobStreetAddress(job);
  if (streetAddress) address.streetAddress = streetAddress;
  if (postalCode) address.postalCode = postalCode;
  return address;
}

export type JobPostingBaseSalary = {
  '@type': 'MonetaryAmount';
  currency: string;
  value: { '@type': 'QuantitativeValue'; value: number; unitText: string };
};

/** Inclui baseSalary apenas quando há um valor real informado pelo empregador. */
export function buildBaseSalary(
  salary: string | null | undefined,
): JobPostingBaseSalary | null {
  const amount = parseJobSalaryAmount(salary);
  if (amount == null || amount <= 0) return null;
  return {
    '@type': 'MonetaryAmount',
    currency: 'BRL',
    value: { '@type': 'QuantitativeValue', value: amount, unitText: 'MONTH' },
  };
}

/** Não publica JobPosting para vaga inativa, expirada ou sem candidatura válida. */
export function buildPublicJobPostingSchema(
  job: JobForSchema,
  publiclyVisible: boolean,
  settings?: SiteSettingsMap,
): Record<string, unknown> | undefined {
  if (!publiclyVisible) return undefined;
  return buildJobPostingSchema(job, settings);
}

export function buildJobPostingSchema(job: JobForSchema, settings?: SiteSettingsMap): Record<string, unknown> {
  const address = buildPostalAddress(job);

  const hiringOrganization: Record<string, unknown> = {
    '@type': 'Organization',
    name: job.company.name,
  };
  const companyLogo = resolveOrganizationLogoUrl(job.company.logo, settings);
  if (companyLogo) hiringOrganization.logo = companyLogo;
  const companyWebsite = sanitizeSchemaUrl(job.company.website);
  if (companyWebsite) hiringOrganization.sameAs = companyWebsite;

  const descriptionHtml = sanitizeJobDescriptionHtml(job.description);
  const schema: Record<string, unknown> = {
    '@context': 'https://schema.org',
    '@type': 'JobPosting',
    title: job.title,
    description: descriptionHtml,
    datePosted: formatJobPostingDate(job.publishedAt),
    hiringOrganization,
    jobLocation: { '@type': 'Place', address },
    identifier: {
      '@type': 'PropertyValue',
      name: siteConfig.name,
      value: String(job.id),
    },
    url: baseUrl(`/vagas/${job.slug}`, settings),
    /** Agregador: candidatura no site/e-mail da empresa, não no Vagas RJ RIO. */
    directApply: false,
  };

  const employmentType = normalizeEmploymentType(job.employmentType);
  if (employmentType) schema.employmentType = employmentType;

  if (job.validThrough) {
    schema.validThrough = formatJobPostingValidThrough(job.validThrough, job.publishedAt);
  }

  const baseSalary = buildBaseSalary(job.salary);
  if (baseSalary) schema.baseSalary = baseSalary;

  const channel = parseApplyChannel(job.applyUrl);
  if (isValidApplyChannel(channel)) {
    const contact: Record<string, unknown> = {
      '@type': 'ContactPoint',
      contactType: 'application',
    };
    if (channel.type === 'email' && channel.email) {
      contact.email = channel.email;
    } else if (channel.type === 'url' && channel.href) {
      contact.url = channel.href;
    } else if ((channel.type === 'whatsapp' || channel.type === 'phone') && channel.telephone) {
      contact.telephone = channel.telephone;
      if (channel.href?.startsWith('http')) contact.url = channel.href;
    }
    schema.applicationContact = contact;
  }

  return schema;
}
