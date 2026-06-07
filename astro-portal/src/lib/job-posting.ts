import { absoluteUrl, baseUrl, siteConfig } from './config';
import { formatJobPostingDate, formatJobPostingValidThrough } from './datetime-brazil';
import { parseJobSalaryAmount, SALARY_DISPLAY_FALLBACK } from './format';
import { isValidApplyChannel, parseApplyChannel } from './apply-channel';
import { isRealCompanyLogo, sanitizeSchemaUrl } from './public-content';
import type { SiteSettingsMap } from './site-settings';

/** Ícone quadrado do portal — fallback em hiringOrganization.logo (JobPosting). */
export const SITE_LOGO_SCHEMA_PATH = '/assets/img/logo-vagas-rj-jobposting.svg';

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

function htmlToPlain(html: string): string {
  return html
    .replace(/<script[\s\S]*?<\/script>/gi, ' ')
    .replace(/<style[\s\S]*?<\/style>/gi, ' ')
    .replace(/<[^>]+>/g, ' ')
    .replace(/\s+/g, ' ')
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
): string {
  if (companyLogo && isRealCompanyLogo(companyLogo)) {
    const raw = sanitizeSchemaUrl(companyLogo) || companyLogo.trim();
    if (raw.startsWith('http://') || raw.startsWith('https://')) return raw;
    const path = raw.startsWith('/') ? raw : `/${raw}`;
    return absoluteUrl(path, settings);
  }
  return absoluteUrl(SITE_LOGO_SCHEMA_PATH, settings);
}

function buildPostalAddress(job: JobForSchema): Record<string, string> {
  const postalCode = siteConfig.cityPostalCodes[job.city.name];
  const address: Record<string, string> = {
    '@type': 'PostalAddress',
    streetAddress: resolveJobStreetAddress(job) ?? STREET_ADDRESS_FALLBACK,
    addressLocality: job.city.name,
    addressRegion: siteConfig.mainUf,
    addressCountry: 'BR',
  };
  if (postalCode) address.postalCode = postalCode;
  return address;
}

export type JobPostingBaseSalary =
  | string
  | {
      '@type': 'MonetaryAmount';
      currency: string;
      value: { '@type': 'QuantitativeValue'; value: number; unitText: string };
    };

/** baseSalary estruturado (valor real) ou texto "A combinar". */
export function buildBaseSalary(salary: string | null | undefined): JobPostingBaseSalary {
  const amount = parseJobSalaryAmount(salary);
  if (amount != null && amount > 0) {
    return {
      '@type': 'MonetaryAmount',
      currency: 'BRL',
      value: { '@type': 'QuantitativeValue', value: amount, unitText: 'MONTH' },
    };
  }
  return SALARY_DISPLAY_FALLBACK;
}

export function buildJobPostingSchema(job: JobForSchema, settings?: SiteSettingsMap): Record<string, unknown> {
  const address = buildPostalAddress(job);

  const hiringOrganization: Record<string, unknown> = {
    '@type': 'Organization',
    name: job.company.name,
    logo: resolveOrganizationLogoUrl(job.company.logo, settings),
  };
  const companyWebsite = sanitizeSchemaUrl(job.company.website);
  if (companyWebsite) hiringOrganization.sameAs = companyWebsite;

  const plainDescription = htmlToPlain(job.description);
  const schema: Record<string, unknown> = {
    '@context': 'https://schema.org',
    '@type': 'JobPosting',
    title: job.title,
    description: plainDescription,
    datePosted: formatJobPostingDate(job.publishedAt),
    validThrough: formatJobPostingValidThrough(job.validThrough, job.publishedAt),
    hiringOrganization,
    jobLocation: { '@type': 'Place', address },
    identifier: {
      '@type': 'PropertyValue',
      name: siteConfig.name,
      value: String(job.id),
    },
    url: baseUrl(`/vagas/${job.slug}`, settings),
    /** Agregador: candidatura no site/e-mail da empresa, não no Vagas RJ. */
    directApply: false,
  };

  const employmentType = normalizeEmploymentType(job.employmentType);
  if (employmentType) schema.employmentType = employmentType;

  schema.baseSalary = buildBaseSalary(job.salary);

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
