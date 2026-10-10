const ADSENSE_PLACEHOLDER = /pub-0{16}\b/i;
const ADSENSE_DIRECT_LINE =
  /^google\.com\s*,\s*pub-\d{16}\s*,\s*DIRECT\s*,\s*f08c47fec0942fa0(?:\s*#.*)?$/i;

/**
 * Publica ads.txt somente quando existe um publisher ID real do AdSense.
 * Linhas de exemplo nunca devem aparecer como autorização válida.
 */
export function sanitizeAdsTxt(raw: string | null | undefined): string {
  const lines = String(raw || '')
    .split(/\r?\n/)
    .map((line) => line.trim())
    .filter(Boolean)
    .filter((line) => !ADSENSE_PLACEHOLDER.test(line));

  if (!lines.some((line) => ADSENSE_DIRECT_LINE.test(line))) return '';
  return lines.join('\n');
}
