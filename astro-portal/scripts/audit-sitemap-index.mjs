/**
 * Auditoria do sitemap index e sub-sitemaps.
 * Uso: DATABASE_URL=file:./dev.sqlite node scripts/audit-sitemap-index.mjs
 */
import { PrismaClient } from '@prisma/client';

process.env.DATABASE_URL = process.env.DATABASE_URL || 'file:./dev.sqlite';
const base = (process.env.SITE_BASE_URL || 'http://localhost:4321').replace(/\/$/, '');

const prisma = new PrismaClient();
const now = new Date();
const jobWhere = {
  isActive: true,
  isIndexable: true,
  publishedAt: { lte: now },
  title: { not: '' },
  slug: { not: '' },
  description: { not: '' },
};

const [pages, jobs, posts, companies, cities] = await Promise.all([
  Promise.resolve(10),
  prisma.job.count({ where: jobWhere }),
  prisma.blogPost.count({ where: { isActive: true, isIndexable: true, title: { not: '' }, slug: { not: '' } } }),
  prisma.company.count({ where: { slug: { not: '' }, name: { not: '' } } }),
  prisma.city.count({ where: { slug: { not: '' }, jobs: { some: jobWhere } } }),
]);

const chunk = (n) => Math.max(1, Math.ceil(n / 1000));
const indexCount =
  1 + chunk(jobs) + chunk(posts) + chunk(companies) + chunk(cities);

console.log('=== Auditoria Sitemap Index ===');
console.log('SITE_BASE_URL:', base);
console.log('Sitemaps no índice (estimado):', indexCount);
console.log('  sitemap-pages.xml:', pages, 'URLs');
console.log('  sitemap-vagas-*.xml:', jobs, 'URLs em', chunk(jobs), 'arquivo(s)');
console.log('  sitemap-blog-*.xml:', posts, 'URLs em', chunk(posts), 'arquivo(s)');
console.log('  sitemap-empresas-*.xml:', companies, 'URLs em', chunk(companies), 'arquivo(s)');
console.log('  sitemap-cidades-*.xml:', cities, 'URLs em', chunk(cities), 'arquivo(s)');
console.log('Algum > 1000 URLs?', [
  jobs > 1000 && 'vagas',
  posts > 1000 && 'blog',
  companies > 1000 && 'empresas',
  cities > 1000 && 'cidades',
].filter(Boolean).join(', ') || 'nenhum');
console.log('/admin e /api: fora do sitemap (por design)');
console.log('robots.txt deve apontar:', `${base}/sitemap.xml`);

await prisma.$disconnect();
