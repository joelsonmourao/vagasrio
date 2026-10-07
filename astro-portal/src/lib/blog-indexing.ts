export const BULK_EDITORIAL_PREFIX = 'rj-local-';

/**
 * O pacote rj-local foi gerado em massa e precisa de revisão editorial
 * individual antes de voltar ao índice. As páginas continuam acessíveis,
 * mas não devem disputar busca nem entrar no sitemap enquanto estiverem
 * nesta fila de revisão.
 */
export function requiresEditorialReview(slug: string | null | undefined): boolean {
  return Boolean(slug?.trim().toLowerCase().startsWith(BULK_EDITORIAL_PREFIX));
}

export function blogPostCanBeIndexed(
  post: { slug: string; isIndexable?: boolean },
  indexingOn: boolean,
): boolean {
  return indexingOn && post.isIndexable !== false && !requiresEditorialReview(post.slug);
}
