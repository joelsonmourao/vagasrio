const ACCENT_MAP: Record<string, string> = {
  á: 'a', à: 'a', â: 'a', ã: 'a', ä: 'a',
  é: 'e', è: 'e', ê: 'e', ë: 'e',
  í: 'i', ì: 'i', î: 'i', ï: 'i',
  ó: 'o', ò: 'o', ô: 'o', õ: 'o', ö: 'o',
  ú: 'u', ù: 'u', û: 'u', ü: 'u',
  ç: 'c', ñ: 'n',
};

/** Remove acentos e gera slug sem hífen no meio da palavra. */
export function slugify(value: string): string {
  let s = value.trim().toLowerCase();
  for (const [from, to] of Object.entries(ACCENT_MAP)) {
    s = s.replaceAll(from, to);
  }
  s = s.normalize('NFD').replace(/\p{M}/gu, '');
  s = s.replace(/[^a-z0-9]+/g, '-').replace(/-+/g, '-').replace(/^-+|-+$/g, '');
  return s || 'item';
}

export async function uniqueJobSlug(base: string, exists: (slug: string) => Promise<boolean>): Promise<string> {
  let slug = slugify(base);
  let candidate = slug;
  let i = 2;
  while (await exists(candidate)) {
    candidate = `${slug}-${i}`;
    i += 1;
  }
  return candidate;
}
