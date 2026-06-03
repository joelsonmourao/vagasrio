/** Normaliza texto para busca parcial (minúsculas, sem acentos). */
export function normalizeSearchText(value: string): string {
  return value
    .trim()
    .toLowerCase()
    .normalize('NFD')
    .replace(/\p{M}/gu, '')
    .replace(/\s+/g, ' ');
}

/** Verifica se `haystack` contém `needle` (parcial, sem acentos, case-insensitive). */
export function matchesPartial(haystack: string, needle: string): boolean {
  const h = normalizeSearchText(haystack);
  const n = normalizeSearchText(needle);
  if (!n) return true;
  return h.includes(n);
}
