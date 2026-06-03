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

export const SALARY_DISPLAY_FALLBACK = 'A combinar';

const SALARY_PLACEHOLDER_RE =
  /a\s*combinar|combinar|negociar|a\s*definir|sob\s*consulta|n[aã]o\s+informado|n[aã]o\s+especificad[oa]/i;

/** Valor numérico mensal quando há salário real (null = usar "A combinar"). */
export function parseJobSalaryAmount(salary: string | null | undefined): number | null {
  if (!salary?.trim()) return null;
  if (SALARY_PLACEHOLDER_RE.test(salary.trim())) return null;
  const m = salary.replace(/\./g, '').match(/(\d{3,})/);
  if (!m) return null;
  const amount = Number(m[1]);
  return amount > 0 ? amount : null;
}

/** Texto exibido na página e nos cards. */
export function formatSalaryDisplay(salary: string | null | undefined): string {
  const amount = parseJobSalaryAmount(salary);
  if (amount) {
    return new Intl.NumberFormat('pt-BR', {
      style: 'currency',
      currency: 'BRL',
      maximumFractionDigits: 0,
    }).format(amount);
  }
  return SALARY_DISPLAY_FALLBACK;
}

/** @deprecated Use formatSalaryDisplay */
export const formatJobSalaryDisplay = formatSalaryDisplay;
