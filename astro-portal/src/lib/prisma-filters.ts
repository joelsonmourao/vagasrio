/** Filtros compatíveis com SQLite (local) e PostgreSQL (producao). */

export function isPostgresDatabase(): boolean {
  const url = process.env.DATABASE_URL || '';
  return url.startsWith('postgresql') || url.startsWith('postgres://');
}

/** `contains` com busca case-insensitive apenas no PostgreSQL. */
export function containsFilter(value: string): { contains: string; mode?: 'insensitive' } {
  if (isPostgresDatabase()) {
    return { contains: value, mode: 'insensitive' };
  }
  return { contains: value };
}

/** `equals` case-insensitive apenas no PostgreSQL. */
export function equalsFilter(value: string): { equals: string; mode?: 'insensitive' } {
  if (isPostgresDatabase()) {
    return { equals: value, mode: 'insensitive' };
  }
  return { equals: value };
}
