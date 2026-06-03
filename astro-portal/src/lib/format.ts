import { formatJobDisplayDate, resolveJobValidThroughDate } from './datetime-brazil';

/** Data em pt-BR — alinhada ao JobPosting (sem “voltar um dia” por UTC). */
export function formatDateBr(date: Date | string): string {
  const d = typeof date === 'string' ? new Date(date) : date;
  return formatJobDisplayDate(d);
}

export function formatJobPublishedBr(publishedAt: Date): string {
  return formatJobDisplayDate(publishedAt);
}

export function formatJobValidThroughBr(validThrough: Date | null, publishedAt: Date): string {
  return formatJobDisplayDate(resolveJobValidThroughDate(validThrough, publishedAt));
}

export function formatDatetimeIsoAttr(date: Date | string): string {
  const d = typeof date === 'string' ? new Date(date) : date;
  return d.toISOString();
}

export function excerpt(html: string, max = 155): string {
  const plain = html.replace(/<[^>]+>/g, ' ').replace(/\s+/g, ' ').trim();
  if (plain.length <= max) return plain;
  return `${plain.slice(0, max - 1).trim()}…`;
}

const EMPLOYMENT_LABELS: Record<string, string> = {
  FULL_TIME: 'Tempo integral',
  PART_TIME: 'Meio período',
  CONTRACTOR: 'PJ / Contrato',
  TEMPORARY: 'Temporário',
  INTERN: 'Estágio',
  VOLUNTEER: 'Voluntário',
  PER_DIEM: 'Diária',
  OTHER: 'Outro',
};

export function employmentTypeLabel(value: string | null | undefined): string | null {
  if (!value) return null;
  const key = value.toUpperCase();
  return EMPLOYMENT_LABELS[key] ?? value;
}

export function applyButtonLabel(url: string): string {
  if (/^mailto:/i.test(url) || (url.includes('@') && !url.startsWith('http'))) {
    return 'Enviar currículo por e-mail';
  }
  return 'Candidatar-se';
}
