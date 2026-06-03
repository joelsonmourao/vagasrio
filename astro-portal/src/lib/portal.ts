import { prisma } from './db';
import { siteConfig } from './config';
import { containsFilter } from './prisma-filters';
import { matchesPartial } from './search-normalize';
import { jobIsPubliclyVisible, normalizeApplyUrl, publicJobPrismaFilter } from './public-content';
import { slugify, uniqueJobSlug } from './slug';
import {
  buildSitemapManifest,
  fetchSitemapPages,
  fetchSitemapJobs,
  fetchSitemapPosts,
  fetchSitemapCompanies,
  fetchSitemapCities,
} from './sitemap-data';
import { entryTimestampsToDate } from './sitemap-xml';

export { SITEMAP_PAGE_PATHS as SITEMAP_STATIC_PATHS } from './sitemap-data';

const jobInclude = {
  company: true,
  city: true,
  category: true,
} as const;

/** Busca parcial por nome ou slug de cidade (ex.: "duque" → Duque de Caxias). */
export async function resolveCityIdsForFilter(cityQuery: string): Promise<number[] | null> {
  const q = cityQuery.trim();
  if (!q) return null;

  const cities = await prisma.city.findMany({
    where: { state: siteConfig.mainUf },
  });

  const matches = cities.filter((city) => {
    if (city.slug === q) return true;
    if (matchesPartial(city.name, q)) return true;
    const slugAsText = city.slug.replace(/-rj$/i, '').replace(/-/g, ' ');
    return matchesPartial(slugAsText, q);
  });

  return matches.map((c) => c.id);
}

export async function dashboardStats() {
  const now = new Date();
  const publicWhere = { state: siteConfig.mainUf, ...publicJobPrismaFilter(now) };

  const [
    jobs,
    activeJobs,
    publicJobs,
    demoJobs,
    expiredJobs,
    posts,
    companies,
    cities,
    recentJobs,
    recentPosts,
  ] = await Promise.all([
    prisma.job.count(),
    prisma.job.count({ where: { isActive: true, state: siteConfig.mainUf } }),
    prisma.job.count({ where: publicWhere }),
    prisma.job.count({ where: { isDemo: true } }),
    prisma.job.count({
      where: {
        OR: [{ isActive: false }, { validThrough: { lt: now } }],
      },
    }),
    prisma.blogPost.count({ where: { isActive: true } }),
    prisma.company.count(),
    prisma.city.count(),
    prisma.job.findMany({
      orderBy: { createdAt: 'desc' },
      take: 5,
      include: jobInclude,
    }),
    prisma.blogPost.findMany({
      where: { isActive: true },
      orderBy: { publishedAt: 'desc' },
      take: 5,
      include: { category: true },
    }),
  ]);

  const hiddenActiveJobs = Math.max(0, activeJobs - publicJobs);

  return {
    jobs,
    activeJobs,
    publicJobs,
    demoJobs,
    hiddenActiveJobs,
    expiredJobs,
    posts,
    companies,
    cities,
    recentJobs,
    recentPosts,
  };
}

export async function publicHomeStats() {
  const now = new Date();
  const jobWhere = { state: siteConfig.mainUf, ...publicJobPrismaFilter(now) };
  const [activeJobs, posts, cities] = await Promise.all([
    prisma.job.count({ where: jobWhere }),
    prisma.blogPost.count({ where: { isActive: true, isIndexable: true } }),
    prisma.city.count({ where: { state: siteConfig.mainUf } }),
  ]);
  return { activeJobs, posts, cities, companies: 0, jobs: activeJobs };
}

export async function homeData() {
  const now = new Date();
  const jobWhere = { state: siteConfig.mainUf, ...publicJobPrismaFilter(now) };
  const [featuredJobs, recentPosts, stats] = await Promise.all([
    prisma.job.findMany({
      where: jobWhere,
      include: jobInclude,
      orderBy: { publishedAt: 'desc' },
      take: 6,
    }),
    prisma.blogPost.findMany({
      where: { isActive: true },
      include: { category: true },
      orderBy: { publishedAt: 'desc' },
      take: 4,
    }),
    publicHomeStats(),
  ]);
  return { featuredJobs, recentPosts, stats };
}

export type JobFilters = {
  page?: number;
  perPage?: number;
  q?: string;
  city?: string;
  company?: string;
  category?: string;
  state?: string;
  activeOnly?: boolean;
};

export async function jobList(filters: JobFilters = {}) {
  const page = Math.max(1, filters.page ?? 1);
  const perPage = filters.perPage ?? siteConfig.perPage;
  const where: Record<string, unknown> = {};

  if (filters.activeOnly !== false) {
    where.isActive = true;
    Object.assign(where, publicJobPrismaFilter());
  }
  if (filters.state) where.state = filters.state.toUpperCase();
  else where.state = siteConfig.mainUf;

  if (filters.q) {
    where.OR = [
      { title: containsFilter(filters.q) },
      { description: containsFilter(filters.q) },
    ];
  }

  if (filters.city) {
    const cityIds = await resolveCityIdsForFilter(filters.city);
    if (cityIds === null) {
      /* vazio */
    } else if (cityIds.length === 0) {
      where.cityId = -1;
    } else {
      where.cityId = { in: cityIds };
    }
  }

  if (filters.company) {
    const companies = await prisma.company.findMany();
    const matches = companies.filter(
      (c) =>
        c.slug === filters.company ||
        matchesPartial(c.name, filters.company!) ||
        matchesPartial(c.slug.replace(/-/g, ' '), filters.company!),
    );
    if (matches.length === 0) where.companyId = -1;
    else where.companyId = { in: matches.map((c) => c.id) };
  }

  if (filters.category) {
    const cat = await prisma.category.findFirst({ where: { slug: filters.category } });
    if (cat) where.categoryId = cat.id;
    else where.categoryId = -1;
  }

  const [total, jobs] = await Promise.all([
    prisma.job.count({ where }),
    prisma.job.findMany({
      where,
      include: jobInclude,
      orderBy: { publishedAt: 'desc' },
      skip: (page - 1) * perPage,
      take: perPage,
    }),
  ]);

  return {
    jobs,
    total,
    page,
    perPage,
    totalPages: Math.max(1, Math.ceil(total / perPage)),
  };
}

export async function jobBySlug(slug: string) {
  const job = await prisma.job.findFirst({
    where: { slug, isActive: true },
    include: jobInclude,
  });
  if (!job || !jobIsPubliclyVisible(job)) return null;
  return job;
}

export async function jobById(id: number, admin = false) {
  const job = await prisma.job.findUnique({ where: { id }, include: jobInclude });
  if (!admin && job && !job.isActive) return null;
  return job;
}

export async function cityBySlug(slug: string) {
  return prisma.city.findFirst({ where: { slug } });
}

export async function companyBySlug(slug: string) {
  return prisma.company.findFirst({ where: { slug } });
}

export async function citiesWithStats() {
  const cities = await prisma.city.findMany({ orderBy: { name: 'asc' } });
  const counts = await prisma.job.groupBy({
    by: ['cityId'],
    where: { isActive: true, state: siteConfig.mainUf },
    _count: true,
  });
  const map = new Map(counts.map((c) => [c.cityId, c._count]));
  return cities.map((c) => ({ ...c, jobCount: map.get(c.id) ?? 0 }));
}

export async function companiesWithStats() {
  const companies = await prisma.company.findMany({ orderBy: { name: 'asc' } });
  const counts = await prisma.job.groupBy({
    by: ['companyId'],
    where: { isActive: true },
    _count: true,
  });
  const map = new Map(counts.map((c) => [c.companyId, c._count]));
  return companies.map((c) => ({ ...c, jobCount: map.get(c.id) ?? 0 }));
}

export async function articleList(opts: { page?: number; perPage?: number; category?: string; q?: string; activeOnly?: boolean } = {}) {
  const page = Math.max(1, opts.page ?? 1);
  const perPage = opts.perPage ?? siteConfig.perPage;
  const where: Record<string, unknown> = {};
  if (opts.activeOnly !== false) where.isActive = true;
  if (opts.category) {
    const cat = await prisma.blogCategory.findFirst({ where: { slug: opts.category } });
    if (cat) where.categoryId = cat.id;
  }
  if (opts.q) {
    where.OR = [
      { title: containsFilter(opts.q) },
      { excerpt: containsFilter(opts.q) },
    ];
  }
  const [total, articles] = await Promise.all([
    prisma.blogPost.count({ where }),
    prisma.blogPost.findMany({
      where,
      include: { category: true },
      orderBy: { publishedAt: 'desc' },
      skip: (page - 1) * perPage,
      take: perPage,
    }),
  ]);
  return { articles, total, page, perPage, totalPages: Math.max(1, Math.ceil(total / perPage)) };
}

export async function articleBySlug(slug: string) {
  return prisma.blogPost.findFirst({
    where: { slug, isActive: true },
    include: { category: true },
  });
}

export async function blogPostById(id: number) {
  return prisma.blogPost.findUnique({ where: { id }, include: { category: true } });
}

export async function blogCategories(activeOnly = true) {
  return prisma.blogCategory.findMany({
    where: activeOnly ? { isActive: true } : undefined,
    orderBy: { name: 'asc' },
  });
}

export async function ensureCompany(name: string) {
  const slug = slugify(name);
  let c = await prisma.company.findFirst({ where: { OR: [{ name }, { slug }] } });
  if (!c) {
    c = await prisma.company.create({ data: { name, slug } });
  }
  return c;
}

export async function ensureCity(name: string, state: string) {
  const slug = slugify(`${name}-${state.toLowerCase()}`);
  let c = await prisma.city.findFirst({ where: { slug } });
  if (!c) {
    c = await prisma.city.create({ data: { name, slug, state: state.toUpperCase() } });
  }
  return c;
}

export async function ensureCategory(name: string) {
  const slug = slugify(name);
  let c = await prisma.category.findFirst({ where: { OR: [{ name }, { slug }] } });
  if (!c) c = await prisma.category.create({ data: { name, slug } });
  return c;
}

export async function saveJob(data: Record<string, string | number | boolean>, id?: number) {
  const title = String(data.title || '').trim();
  const companyName = String(data.company || data.company_name || '').trim();
  const cityName = String(data.city || data.city_name || '').trim();
  const state = String(data.state || siteConfig.mainUf).trim().toUpperCase();
  const description = String(data.description || '').trim();
  const applyRaw = String(data.apply_url || data.applyUrl || '').trim() || null;
  const applyUrl = applyRaw ? normalizeApplyUrl(applyRaw) : null;

  if (!title || !companyName || !cityName || !description) {
    throw new Error('Preencha título, empresa, cidade e descrição.');
  }

  const company = await ensureCompany(companyName);
  const city = await ensureCity(cityName, state);
  let categoryId: number | null = null;
  const categoryName = String(data.category || '').trim();
  if (categoryName) categoryId = (await ensureCategory(categoryName)).id;

  const publishedInput = data.published_at || data.publishedAt;
  const validInput = data.valid_through || data.validThrough;
  const publishedAt = publishedInput ? new Date(String(publishedInput)) : null;
  const validThrough = validInput ? new Date(String(validInput)) : null;

  const seoTitle = String(data.seo_title || data.seoTitle || '').trim() || null;
  const seoDescription = String(data.seo_description || data.seoDescription || '').trim() || null;
  const canonicalUrl = String(data.canonical_url || data.canonicalUrl || '').trim() || null;
  const isIndexable = !['0', 'false', 'off'].includes(String(data.is_indexable ?? '1').toLowerCase());

  const payload = {
    title,
    companyId: company.id,
    cityId: city.id,
    categoryId,
    state,
    description,
    salary: String(data.salary || '').trim() || null,
    employmentType: String(data.employment_type || data.employmentType || '').trim() || null,
    applyUrl,
    isActive: !['0', 'false', 'off'].includes(String(data.is_active ?? '1').toLowerCase()),
    validThrough,
    seoTitle,
    seoDescription,
    canonicalUrl,
    isIndexable,
  };

  if (id) {
    const existing = await prisma.job.findUnique({ where: { id } });
    if (!existing) throw new Error('Vaga não encontrada.');
    const slugInput = String(data.slug || '').trim();
    const slug = slugInput ? slugify(slugInput) : existing.slug;
    if (slug !== existing.slug) {
      const dup = await prisma.job.findFirst({ where: { slug, NOT: { id } } });
      if (dup) throw new Error('Este endereço da página já está em uso. Escolha outro.');
    }
    const updateData: typeof payload & { slug: string; publishedAt?: Date } = { ...payload, slug };
    if (publishedAt) updateData.publishedAt = publishedAt;
    return prisma.job.update({ where: { id }, data: updateData });
  }

  const slug = await uniqueJobSlug(`${title}-${city.name}-${state}`, async (s) => {
    const found = await prisma.job.findFirst({ where: { slug: s } });
    return !!found;
  });

  return prisma.job.create({
    data: { ...payload, slug, publishedAt: publishedAt ?? new Date() },
  });
}

export async function toggleJob(id: number) {
  const job = await prisma.job.findUnique({ where: { id } });
  if (!job) throw new Error('Vaga não encontrada.');
  return prisma.job.update({ where: { id }, data: { isActive: !job.isActive } });
}

export async function deleteJob(id: number) {
  return prisma.job.delete({ where: { id } });
}

export async function saveBlogPost(data: Record<string, string | number | boolean>, id?: number) {
  const title = String(data.title || '').trim();
  const categoryId = Number(data.category_id || data.categoryId);
  const excerpt = String(data.excerpt || '').trim();
  const content = String(data.content || '').trim();
  if (!title || !categoryId || !excerpt || !content) {
    throw new Error('Preencha título, categoria, resumo e conteúdo.');
  }
  const slugInput = String(data.slug || '').trim();
  const publishedAt = data.published_at ? new Date(String(data.published_at)) : new Date();
  const seoTitle = String(data.seo_title || data.seoTitle || '').trim() || null;
  const seoDescription = String(data.seo_description || data.seoDescription || '').trim() || null;
  const payload = {
    title,
    categoryId,
    excerpt,
    content,
    seoTitle,
    seoDescription,
    featuredImage: String(data.featured_image || data.featuredImage || '').trim() || null,
    canonicalUrl: String(data.canonical_url || data.canonicalUrl || '').trim() || null,
    isIndexable: !['0', 'false', 'off'].includes(String(data.is_indexable ?? '1').toLowerCase()),
    publishedAt,
    isActive: !['0', 'false', 'off'].includes(String(data.is_active ?? '1').toLowerCase()),
  };

  if (id) {
    const existing = await prisma.blogPost.findUnique({ where: { id } });
    if (!existing) throw new Error('Artigo não encontrado.');
    const slug = slugInput ? slugify(slugInput) : existing.slug;
    const dup = await prisma.blogPost.findFirst({ where: { slug, NOT: { id } } });
    if (dup) throw new Error('Este endereço da página já está em uso.');
    return prisma.blogPost.update({ where: { id }, data: { ...payload, slug } });
  }

  const slug = slugInput ? slugify(slugInput) : slugify(title);
  const dup = await prisma.blogPost.findFirst({ where: { slug } });
  if (dup) throw new Error('Este endereço da página já está em uso.');
  return prisma.blogPost.create({ data: { ...payload, slug } });
}

export async function toggleBlogPost(id: number) {
  const post = await prisma.blogPost.findUnique({ where: { id } });
  if (!post) throw new Error('Artigo não encontrado.');
  return prisma.blogPost.update({ where: { id }, data: { isActive: !post.isActive } });
}

export async function deleteBlogPost(id: number) {
  return prisma.blogPost.delete({ where: { id } });
}

export async function getLastImportSummary() {
  const last = await prisma.import.findFirst({ orderBy: { createdAt: 'desc' } });
  if (!last) return null;
  const errors = await prisma.importError.findMany({
    where: { importId: last.id },
    orderBy: { rowNumber: 'asc' },
    take: 30,
  });
  return { ...last, errors };
}

export type SitemapEntry = { loc: string; lastmod: Date };

/** @deprecated use buildSitemapManifest — lista plana para compatibilidade */
export async function buildSitemapData(): Promise<{
  urls: SitemapEntry[];
  stats: { total: number; static: number; jobs: number; posts: number; companies: number; cities: number };
}> {
  const [pages, jobs, posts, companies, cities] = await Promise.all([
    fetchSitemapPages(),
    fetchSitemapJobs(),
    fetchSitemapPosts(),
    fetchSitemapCompanies(),
    fetchSitemapCities(),
  ]);
  const toLegacy = (e: { path: string; timestamps: import('./sitemap-xml').SitemapTimestamps }) => ({
    loc: e.path,
    lastmod: entryTimestampsToDate(e.timestamps),
  });
  const urls: SitemapEntry[] = [
    ...pages.map(toLegacy),
    ...jobs.map(toLegacy),
    ...posts.map(toLegacy),
    ...companies.map(toLegacy),
    ...cities.map(toLegacy),
  ];
  return {
    urls,
    stats: {
      total: urls.length,
      static: pages.length,
      jobs: jobs.length,
      posts: posts.length,
      companies: companies.length,
      cities: cities.length,
    },
  };
}

/** @deprecated */
export async function sitemapUrls(): Promise<SitemapEntry[]> {
  const { urls } = await buildSitemapData();
  return urls;
}

export { buildSitemapManifest };
