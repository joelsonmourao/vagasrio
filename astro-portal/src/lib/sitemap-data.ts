import { prisma } from './db';
import { siteConfig } from './config';
import { publicJobPrismaFilter } from './public-content';
import { getStaticSiteLastmodDate, getStaticSiteTimestamps } from './datetime-brazil';
import {
  chunkList,
  entryTimestampsToDate,
  SITEMAP_MAX_URLS,
  sitemapFilename,
  type SitemapFileRef,
  type SitemapManifest,
  type SitemapTimestamps,
  type SitemapUrlEntry,
} from './sitemap-xml';

export const SITEMAP_PAGE_PATHS = [
  '/',
  '/vagas',
  '/blog',
  '/empresas',
  '/sobre',
  '/contato',
  '/politica-de-privacidade',
  '/politica-de-cookies',
  '/termos-de-uso',
  `/vagas/estado/${siteConfig.mainUf.toLowerCase()}`,
] as const;

function toEntry(path: string, timestamps: SitemapTimestamps): SitemapUrlEntry {
  return { path, timestamps };
}

function isValidSlug(slug: string | null | undefined): slug is string {
  return typeof slug === 'string' && slug.trim().length > 0 && !slug.includes('?');
}

function fileRef(filename: string, entries: SitemapUrlEntry[]): SitemapFileRef {
  const lastmod = maxEntryDate(entries);
  return { filename, lastmod, urlCount: entries.length };
}

function maxEntryDate(entries: SitemapUrlEntry[]): Date {
  if (entries.length === 0) return getStaticSiteLastmodDate();
  let max = entryTimestampsToDate(entries[0].timestamps);
  for (let i = 1; i < entries.length; i++) {
    const d = entryTimestampsToDate(entries[i].timestamps);
    if (d.getTime() > max.getTime()) max = d;
  }
  return max;
}

function manifestChunkFiles(
  type: string,
  entryLists: SitemapUrlEntry[][],
  overLimit: string[],
): SitemapFileRef[] {
  return entryLists.map((entries, i) => {
    const filename = type === 'pages' ? 'sitemap-pages.xml' : sitemapFilename(type, i + 1);
    if (entries.length > SITEMAP_MAX_URLS) overLimit.push(filename);
    return fileRef(filename, entries);
  });
}

function jobWhereIndexable(includeIndexableColumn: boolean) {
  const now = new Date();
  const base = {
    title: { not: '' },
    slug: { not: '' },
    description: { not: '' },
    ...publicJobPrismaFilter(now),
  };

  if (includeIndexableColumn) return base;

  const { isIndexable: _removed, ...withoutIndexable } = base as typeof base & { isIndexable?: boolean };
  return withoutIndexable;
}

export async function fetchSitemapPages(): Promise<SitemapUrlEntry[]> {
  const timestamps = getStaticSiteTimestamps();
  return SITEMAP_PAGE_PATHS.map((path) => toEntry(path, timestamps));
}

export async function fetchSitemapJobs(includeIndexable = true): Promise<SitemapUrlEntry[]> {
  const where = jobWhereIndexable(includeIndexable);
  const rows = await prisma.job.findMany({
    where,
    select: {
      slug: true,
      title: true,
      updatedAt: true,
      publishedAt: true,
      createdAt: true,
    },
    orderBy: { updatedAt: 'desc' },
  });

  return rows
    .filter((r) => isValidSlug(r.slug) && r.title.trim().length > 0)
    .map((r) =>
      toEntry(`/vagas/${r.slug}`, {
        updatedAt: r.updatedAt,
        publishedAt: r.publishedAt,
        createdAt: r.createdAt,
      }),
    );
}

export async function fetchSitemapPosts(includeIndexable = true): Promise<SitemapUrlEntry[]> {
  const where = includeIndexable
    ? { isActive: true, isIndexable: true, title: { not: '' }, slug: { not: '' } }
    : { isActive: true, title: { not: '' }, slug: { not: '' } };

  const rows = await prisma.blogPost.findMany({
    where,
    select: {
      slug: true,
      title: true,
      excerpt: true,
      updatedAt: true,
      publishedAt: true,
      createdAt: true,
    },
    orderBy: { updatedAt: 'desc' },
  });

  return rows
    .filter((r) => isValidSlug(r.slug) && r.title.trim() && r.excerpt.trim())
    .map((r) =>
      toEntry(`/blog/${r.slug}`, {
        updatedAt: r.updatedAt,
        publishedAt: r.publishedAt,
        createdAt: r.createdAt,
      }),
    );
}

export async function fetchSitemapCompanies(): Promise<SitemapUrlEntry[]> {
  const rows = await prisma.company.findMany({
    where: { slug: { not: '' }, name: { not: '' } },
    select: { slug: true, createdAt: true },
    orderBy: { name: 'asc' },
  });

  return rows
    .filter((r) => isValidSlug(r.slug))
    .map((r) => toEntry(`/empresas/${r.slug}`, { createdAt: r.createdAt }));
}

export async function fetchSitemapCities(includeIndexable = true): Promise<SitemapUrlEntry[]> {
  const jobFilter = includeIndexable
    ? { isActive: true, isIndexable: true, ...publicJobPrismaFilter() }
    : { isActive: true, ...publicJobPrismaFilter() };

  const rows = await prisma.city.findMany({
    where: {
      slug: { not: '' },
      jobs: { some: jobFilter },
    },
    select: { slug: true, createdAt: true },
    orderBy: { name: 'asc' },
  });

  return rows
    .filter((r) => isValidSlug(r.slug))
    .map((r) => toEntry(`/vagas/cidade/${r.slug}`, { createdAt: r.createdAt }));
}

async function loadAllEntries(includeIndexable = true) {
  try {
    const [pages, jobs, posts, companies, cities] = await Promise.all([
      fetchSitemapPages(),
      fetchSitemapJobs(includeIndexable),
      fetchSitemapPosts(includeIndexable),
      fetchSitemapCompanies(),
      fetchSitemapCities(includeIndexable),
    ]);
    return { pages, jobs, posts, companies, cities };
  } catch (err) {
    const msg = err instanceof Error ? err.message : String(err);
    if (/is_indexable|isIndexable|no such column/i.test(msg) && includeIndexable) {
      return loadAllEntries(false);
    }
    throw err;
  }
}

export async function buildSitemapManifest(): Promise<SitemapManifest> {
  const { pages, jobs, posts, companies, cities } = await loadAllEntries(true);
  const overLimit: string[] = [];

  const pageFiles = manifestChunkFiles('pages', [pages], overLimit);
  const jobChunks = chunkList(jobs);
  const postChunks = chunkList(posts);
  const companyChunks = chunkList(companies);
  const cityChunks = chunkList(cities);

  const files: SitemapFileRef[] = [
    ...pageFiles,
    ...manifestChunkFiles('vagas', jobChunks, overLimit),
    ...manifestChunkFiles('blog', postChunks, overLimit),
    ...manifestChunkFiles('empresas', companyChunks, overLimit),
    ...manifestChunkFiles('cidades', cityChunks, overLimit),
  ];

  return {
    files,
    stats: {
      indexCount: files.length,
      pages: pages.length,
      jobs: jobs.length,
      posts: posts.length,
      companies: companies.length,
      cities: cities.length,
      jobChunks: jobChunks.length,
      postChunks: postChunks.length,
      companyChunks: companyChunks.length,
      cityChunks: cityChunks.length,
      overLimit,
    },
  };
}

const entryCache = new Map<string, SitemapUrlEntry[]>();

async function ensureCache(): Promise<void> {
  if (entryCache.size > 0) return;
  const data = await loadAllEntries(true);
  entryCache.set('pages', data.pages);
  entryCache.set('jobs', data.jobs);
  entryCache.set('posts', data.posts);
  entryCache.set('companies', data.companies);
  entryCache.set('cities', data.cities);
}

export async function getSitemapBundleEntries(
  bundle: string,
): Promise<SitemapUrlEntry[] | null> {
  if (bundle === 'pages') {
    await ensureCache();
    return entryCache.get('pages') ?? [];
  }

  const m = bundle.match(/^(vagas|blog|empresas|cidades)-(\d+)$/);
  if (!m) return null;

  const type = m[1];
  const index = Number(m[2]) - 1;
  if (index < 0) return null;

  await ensureCache();
  const key =
    type === 'vagas'
      ? 'jobs'
      : type === 'blog'
        ? 'posts'
        : type === 'empresas'
          ? 'companies'
          : 'cities';

  const all = entryCache.get(key) ?? [];
  const chunks = chunkList(all);
  return chunks[index] ?? null;
}

export function clearSitemapCache(): void {
  entryCache.clear();
}
