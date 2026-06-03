/** Ambiente de produção (build/deploy real). */
export function isProductionDeploy(): boolean {
  return process.env.NODE_ENV === 'production';
}

export function isExampleUrl(url: string | null | undefined): boolean {
  if (!url?.trim()) return false;
  const raw = url.trim();
  try {
    const parsed = new URL(raw.includes('://') ? raw : `https://${raw}`);
    const host = parsed.hostname.replace(/^www\./i, '').toLowerCase();
    return host === 'example.com' || host === 'example.org';
  } catch {
    return /example\.(com|org)/i.test(raw);
  }
}

export function isDemoCompanyName(name: string | null | undefined): boolean {
  if (!name?.trim()) return false;
  return /\bdemo\b/i.test(name) || /empresa\s+demo/i.test(name);
}

import { isValidApplyChannel, parseApplyChannel, resolveApplyHref } from './apply-channel';

/**
 * Normaliza para persistência (mailto:, https://, tel:).
 * Não gera path relativo do portal.
 */
export function normalizeApplyUrl(raw: string | null | undefined): string | null {
  return resolveApplyHref(parseApplyChannel(raw));
}

export function isBlockedApplyUrl(url: string | null | undefined): boolean {
  return !isValidApplyChannel(parseApplyChannel(url));
}

/** @deprecated Use parseApplyChannel — mantido para compatibilidade. */
export function resolvePublicApplyUrl(url: string | null | undefined): string | null {
  return resolveApplyHref(parseApplyChannel(url));
}

const SITE_LOGO_MARKERS = ['logo-vagas-rj', 'og-vagas-rj', 'favicon'];

/** Logo da empresa contratante — nunca a marca do portal Vagas RJ. */
export function isRealCompanyLogo(logo: string | null | undefined): boolean {
  if (!logo?.trim()) return false;
  const l = logo.trim().toLowerCase();
  if (isExampleUrl(logo)) return false;
  return !SITE_LOGO_MARKERS.some((m) => l.includes(m));
}

type JobPublicFields = {
  isDemo: boolean;
  isActive?: boolean;
  isIndexable?: boolean;
  validThrough?: Date | null;
  applyUrl: string | null;
  title?: string;
  company?: { name: string; website?: string | null };
};

export function jobIsExpired(job: { validThrough: Date | null }, now = new Date()): boolean {
  return !!job.validThrough && job.validThrough < now;
}

export function jobIsPubliclyVisible(job: JobPublicFields, now = new Date()): boolean {
  if (job.isDemo) return false;
  if (job.isActive === false) return false;
  if (job.isIndexable === false) return false;
  if (jobIsExpired(job)) return false;
  if (/candidatura\s+falsa/i.test(job.title || '')) return false;
  if (job.company && isDemoCompanyName(job.company.name)) return false;
  if (isBlockedApplyUrl(job.applyUrl)) return false;
  return true;
}

/** Filtro Prisma: vagas públicas, indexáveis, ativas, sem demo/example.com. */
export function publicJobPrismaFilter(now = new Date()): Record<string, unknown> {
  return {
    isDemo: false,
    isActive: true,
    isIndexable: true,
    publishedAt: { lte: now },
    OR: [{ validThrough: null }, { validThrough: { gte: now } }],
    NOT: {
      OR: [
        { applyUrl: { contains: 'example.com' } },
        { applyUrl: { contains: 'example.org' } },
      ],
    },
  };
}

export function sanitizeSchemaUrl(url: string | null | undefined): string | null {
  if (!url?.trim() || isExampleUrl(url)) return null;
  return url.trim();
}

/** Resumo para painel admin / auditoria Google Jobs. */
export function jobGoogleJobsIssues(job: JobPublicFields & { description?: string }): string[] {
  const issues: string[] = [];
  if (job.isDemo) issues.push('Vaga marcada como demonstração.');
  if (job.isIndexable === false) issues.push('Indexação desativada (não entra no Google Jobs).');
  if (job.isActive === false) issues.push('Vaga inativa.');
  if (jobIsExpired(job)) issues.push('Vaga expirada (validThrough no passado).');
  if (isBlockedApplyUrl(job.applyUrl)) {
    issues.push('Link de candidatura inválido, example.com ou e-mail/URL mal formatado.');
  }
  if (job.company && isDemoCompanyName(job.company.name)) issues.push('Empresa identificada como demo.');
  if (/candidatura\s+falsa/i.test(job.title || '')) issues.push('Título indica candidatura falsa.');
  return issues;
}
