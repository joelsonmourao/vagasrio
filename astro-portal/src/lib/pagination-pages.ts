/** Gera itens de paginação: números e reticências. */
export function buildPaginationItems(
  page: number,
  totalPages: number,
): Array<number | 'ellipsis'> {
  if (totalPages <= 1) return [];
  if (totalPages <= 7) {
    return Array.from({ length: totalPages }, (_, i) => i + 1);
  }

  const items: Array<number | 'ellipsis'> = [1];
  const start = Math.max(2, page - 1);
  const end = Math.min(totalPages - 1, page + 1);

  if (start > 2) items.push('ellipsis');
  for (let i = start; i <= end; i += 1) items.push(i);
  if (end < totalPages - 1) items.push('ellipsis');
  items.push(totalPages);

  return items;
}


/** Normaliza o parâmetro page para um inteiro positivo e seguro. */
export function parsePageParam(value: string | null | undefined): number {
  const parsed = Number(value ?? '1');
  return Number.isSafeInteger(parsed) && parsed > 0 ? parsed : 1;
}

/** Mantém a página 1 limpa e gera URL própria para cada página seguinte. */
export function paginatedPath(basePath: string, page: number): string {
  return page > 1 ? `${basePath}?page=${page}` : basePath;
}
