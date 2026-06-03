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
