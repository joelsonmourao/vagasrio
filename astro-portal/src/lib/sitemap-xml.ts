import type { SiteSettingsMap } from './site-settings';
import { baseUrl } from './config';
import {
  formatLastmod,
  getStaticSiteLastmodDate,
  getStaticSiteLastmodIso,
  pickTimestampDate,
  type TimestampFields,
} from './datetime-brazil';

export const SITEMAP_MAX_URLS = 1000;

export type SitemapTimestamps = TimestampFields;

export type SitemapUrlEntry = {
  path: string;
  timestamps: SitemapTimestamps;
};

export type SitemapFileRef = {
  filename: string;
  lastmod: Date;
  urlCount: number;
};

export type SitemapManifest = {
  files: SitemapFileRef[];
  stats: {
    indexCount: number;
    pages: number;
    jobs: number;
    posts: number;
    companies: number;
    cities: number;
    jobChunks: number;
    postChunks: number;
    companyChunks: number;
    cityChunks: number;
    overLimit: string[];
  };
};

export { formatLastmod } from './datetime-brazil';

/** Escapa texto para XML. */
export function escapeXml(value: string): string {
  return value
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&apos;');
}

/** Divide lista em blocos de até max (padrão 1000). */
export function chunkList<T>(items: T[], max = SITEMAP_MAX_URLS): T[][] {
  if (items.length === 0) return [];
  const chunks: T[][] = [];
  for (let i = 0; i < items.length; i += max) {
    chunks.push(items.slice(i, i + max));
  }
  return chunks;
}

export function absoluteSitemapUrl(path: string, settings?: SiteSettingsMap): string {
  const normalized = path.startsWith('/') ? path : `/${path}`;
  return baseUrl(normalized, settings);
}

export function xmlResponse(body: string, extraHeaders?: Record<string, string>): Response {
  return new Response(body, {
    headers: {
      'Content-Type': 'application/xml; charset=UTF-8',
      'Cache-Control': 'public, max-age=3600',
      ...extraHeaders,
    },
  });
}

export function buildUrlsetXml(
  entries: SitemapUrlEntry[],
  settings?: SiteSettingsMap,
): string {
  const staticFallback = getStaticSiteLastmodIso();
  const urls = entries
    .map(
      (e) => `  <url>
    <loc>${escapeXml(absoluteSitemapUrl(e.path, settings))}</loc>
    <lastmod>${formatLastmod(e.timestamps, staticFallback)}</lastmod>
  </url>`,
    )
    .join('\n');

  return `<?xml version="1.0" encoding="UTF-8"?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
${urls}
</urlset>`;
}

export function buildSitemapIndexXml(
  files: SitemapFileRef[],
  settings?: SiteSettingsMap,
): string {
  const staticFallback = getStaticSiteLastmodIso();
  const items = files
    .map((f) => {
      const lastmod = formatLastmod({ updatedAt: f.lastmod }, staticFallback);
      return `  <sitemap>
    <loc>${escapeXml(absoluteSitemapUrl(`/${f.filename}`, settings))}</loc>
    <lastmod>${lastmod}</lastmod>
  </sitemap>`;
    })
    .join('\n');

  return `<?xml version="1.0" encoding="UTF-8"?>
<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
${items}
</sitemapindex>`;
}

export function emptyUrlsetXml(): string {
  return `<?xml version="1.0" encoding="UTF-8"?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
</urlset>`;
}

export function emptySitemapIndexXml(): string {
  return `<?xml version="1.0" encoding="UTF-8"?>
<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
</sitemapindex>`;
}

export function sitemapFilename(type: string, chunkIndex: number): string {
  return `sitemap-${type}-${chunkIndex}.xml`;
}

export function parseSitemapBundle(name: string): { type: string; chunk: number } | null {
  if (name === 'pages') return { type: 'pages', chunk: 1 };
  const m = name.match(/^(vagas|blog|empresas|cidades)-(\d+)$/);
  if (!m) return null;
  const chunk = Number(m[2]);
  if (!Number.isFinite(chunk) || chunk < 1) return null;
  return { type: m[1], chunk };
}

export function entryTimestampsToDate(timestamps: SitemapTimestamps): Date {
  return pickTimestampDate(timestamps) ?? getStaticSiteLastmodDate();
}
