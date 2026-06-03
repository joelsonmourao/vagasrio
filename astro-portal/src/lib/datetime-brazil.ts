/**
 * Datas em ISO 8601 com fuso do Brasil (America/Sao_Paulo, offset -03:00).
 */

export const BRAZIL_TZ = 'America/Sao_Paulo';
export const BRAZIL_OFFSET = '-03:00';

/** Validade padrão de vaga no JobPosting quando validThrough não está no banco (dias). */
export const JOB_DEFAULT_VALIDITY_DAYS = 30;

export type TimestampFields = {
  updatedAt?: Date | null;
  publishedAt?: Date | null;
  createdAt?: Date | null;
};

type BrazilParts = {
  year: string;
  month: string;
  day: string;
  hour: string;
  minute: string;
  second: string;
};

function getBrazilParts(date: Date): BrazilParts {
  const parts = new Intl.DateTimeFormat('en-CA', {
    timeZone: BRAZIL_TZ,
    year: 'numeric',
    month: '2-digit',
    day: '2-digit',
    hour: '2-digit',
    minute: '2-digit',
    second: '2-digit',
    hour12: false,
  }).formatToParts(date);

  const map: Record<string, string> = {};
  for (const p of parts) {
    if (p.type !== 'literal') map[p.type] = p.value;
  }

  return {
    year: map.year,
    month: map.month,
    day: map.day,
    hour: map.hour ?? '00',
    minute: map.minute ?? '00',
    second: map.second ?? '00',
  };
}

function pad2(v: string): string {
  return v.padStart(2, '0');
}

/** Data/hora completa: 2026-06-03T08:00:00-03:00 */
export function formatDateTimeBrazil(date: Date): string {
  const p = getBrazilParts(date);
  return `${p.year}-${p.month}-${p.day}T${pad2(p.hour)}:${pad2(p.minute)}:${pad2(p.second)}${BRAZIL_OFFSET}`;
}

/** Só data → início (00:00:00) ou fim do dia (23:59:59) no fuso BR. */
export function formatDateOnlyBrazil(date: Date, endOfDay = false): string {
  const p = getBrazilParts(date);
  const time = endOfDay ? '23:59:59' : '00:00:00';
  return `${p.year}-${p.month}-${p.day}T${time}${BRAZIL_OFFSET}`;
}

/** Detecta se, no fuso BR, o horário é meia-noite (campo só com data). */
export function isDateOnlyInBrazil(date: Date): boolean {
  const p = getBrazilParts(date);
  return p.hour === '00' && p.minute === '00' && p.second === '00';
}

/** Datas do SQLite/Prisma costumam vir como meia-noite UTC = dia civil. */
export function isUtcDateOnly(date: Date): boolean {
  return (
    date.getUTCHours() === 0 &&
    date.getUTCMinutes() === 0 &&
    date.getUTCSeconds() === 0 &&
    date.getUTCMilliseconds() === 0
  );
}

/** Usa componentes UTC como dia civil (evita “voltar um dia” no fuso BR). */
export function formatCalendarDateUtc(date: Date, endOfDay = false): string {
  const y = date.getUTCFullYear();
  const m = String(date.getUTCMonth() + 1).padStart(2, '0');
  const d = String(date.getUTCDate()).padStart(2, '0');
  const time = endOfDay ? '23:59:59' : '00:00:00';
  return `${y}-${m}-${d}T${time}${BRAZIL_OFFSET}`;
}

/** Formata qualquer Date: com hora preservada ou 00:00:00 se for só data. */
export function formatFromDateSmart(date: Date, endOfDay = false): string {
  if (isUtcDateOnly(date)) return formatCalendarDateUtc(date, endOfDay);
  if (endOfDay) return formatDateOnlyBrazil(date, true);
  if (isDateOnlyInBrazil(date)) return formatDateOnlyBrazil(date, false);
  return formatDateTimeBrazil(date);
}

export function parseBrazilIso(value: string): Date {
  const trimmed = value.trim();
  if (/^\d{4}-\d{2}-\d{2}$/.test(trimmed)) {
    return new Date(`${trimmed}T00:00:00${BRAZIL_OFFSET}`);
  }
  if (/[+-]\d{2}:\d{2}$/.test(trimmed) || trimmed.endsWith('Z')) {
    return new Date(trimmed);
  }
  return new Date(`${trimmed}${BRAZIL_OFFSET}`);
}

/** lastmod configurável para páginas estáticas (env SITE_STATIC_LASTMOD). */
export function getStaticSiteLastmodIso(): string {
  const env = process.env.SITE_STATIC_LASTMOD?.trim();
  if (env) {
    const d = parseBrazilIso(env);
    return formatFromDateSmart(d);
  }
  return `2026-06-03T00:00:00${BRAZIL_OFFSET}`;
}

export function getStaticSiteLastmodDate(): Date {
  return parseBrazilIso(process.env.SITE_STATIC_LASTMOD?.trim() || '2026-06-03T00:00:00-03:00');
}

export function getStaticSiteTimestamps(): TimestampFields {
  return { updatedAt: getStaticSiteLastmodDate() };
}

/** Escolhe o instante usado no lastmod (prioridade: updatedAt → publishedAt → createdAt). */
export function pickTimestampDate(fields: TimestampFields): Date | null {
  const d = fields.updatedAt ?? fields.publishedAt ?? fields.createdAt;
  return d ?? null;
}

/**
 * lastmod do sitemap — prioridade updatedAt > publishedAt > createdAt.
 * Fallback: SITE_STATIC_LASTMOD ou constante (nunca “agora” por URL).
 */
export function formatLastmod(
  fields: TimestampFields,
  fallbackIso?: string,
): string {
  const picked = pickTimestampDate(fields);
  if (picked) return formatFromDateSmart(picked);
  if (fallbackIso) return fallbackIso;
  return getStaticSiteLastmodIso();
}

/** datePosted — sempre publishedAt original (não usar updatedAt). */
export function formatJobPostingDate(publishedAt: Date): string {
  return formatFromDateSmart(publishedAt, false);
}

/**
 * validThrough — fim do dia no fuso BR.
 * Sem validThrough no banco: publishedAt + JOB_DEFAULT_VALIDITY_DAYS (23:59:59).
 */
export function formatJobPostingValidThrough(
  validThrough: Date | null,
  publishedAt: Date,
): string {
  if (validThrough) return formatFromDateSmart(validThrough, true);
  const base = isUtcDateOnly(publishedAt)
    ? new Date(Date.UTC(
        publishedAt.getUTCFullYear(),
        publishedAt.getUTCMonth(),
        publishedAt.getUTCDate() + JOB_DEFAULT_VALIDITY_DAYS,
      ))
    : new Date(publishedAt.getTime() + JOB_DEFAULT_VALIDITY_DAYS * 86400000);
  return formatFromDateSmart(base, true);
}

/** BlogPosting / Article — publicação e alteração com hora BR. */
export function formatSchemaDateTime(date: Date): string {
  return formatFromDateSmart(date, false);
}

/** Valor para input[type=date] alinhado ao dia civil do banco (UTC meia-noite). */
export function formatDateInputValue(date: Date): string {
  if (isUtcDateOnly(date)) {
    const y = date.getUTCFullYear();
    const m = String(date.getUTCMonth() + 1).padStart(2, '0');
    const d = String(date.getUTCDate()).padStart(2, '0');
    return `${y}-${m}-${d}`;
  }
  const p = getBrazilParts(date);
  return `${p.year}-${p.month}-${p.day}`;
}

/** Data exibida na UI da vaga (mesmo dia civil que JobPosting). */
export function formatJobDisplayDate(date: Date): string {
  if (isUtcDateOnly(date)) {
    const d = String(date.getUTCDate()).padStart(2, '0');
    const m = String(date.getUTCMonth() + 1).padStart(2, '0');
    const y = date.getUTCFullYear();
    return `${d}/${m}/${y}`;
  }
  return date.toLocaleDateString('pt-BR', {
    timeZone: BRAZIL_TZ,
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
  });
}

/** validThrough efetivo quando o banco não tem data (+30 dias da publicação). */
export function resolveJobValidThroughDate(validThrough: Date | null, publishedAt: Date): Date {
  if (validThrough) return validThrough;
  if (isUtcDateOnly(publishedAt)) {
    return new Date(
      Date.UTC(
        publishedAt.getUTCFullYear(),
        publishedAt.getUTCMonth(),
        publishedAt.getUTCDate() + JOB_DEFAULT_VALIDITY_DAYS,
      ),
    );
  }
  return new Date(publishedAt.getTime() + JOB_DEFAULT_VALIDITY_DAYS * 86400000);
}

export function maxTimestampDate(entries: TimestampFields[]): Date {
  let max: Date | null = null;
  for (const ts of entries) {
    const d = pickTimestampDate(ts);
    if (d && (!max || d.getTime() > max.getTime())) max = d;
  }
  return max ?? getStaticSiteLastmodDate();
}
