/**
 * Auditoria SEO local — conta URLs do sitemap e valida base URL.
 * Uso: DATABASE_URL=file:./dev.sqlite node scripts/audit-seo.mjs
 */
import { PrismaClient } from '@prisma/client';
import { readFileSync } from 'node:fs';
import { join, dirname } from 'node:path';
import { fileURLToPath } from 'node:url';

const root = join(dirname(fileURLToPath(import.meta.url)), '..');
process.env.DATABASE_URL = process.env.DATABASE_URL || 'file:./dev.sqlite';

const prisma = new PrismaClient();

const STATIC = 10;
const jobWhere = { isActive: true, isIndexable: true };
const postWhere = { isActive: true, isIndexable: true };

const [jobs, posts, cities, companies] = await Promise.all([
  prisma.job.count({ where: jobWhere }),
  prisma.blogPost.count({ where: postWhere }),
  prisma.city.count({ where: { jobs: { some: jobWhere } } }),
  prisma.company.count(),
]);

const total = STATIC + jobs + posts + cities + companies;
const base = (process.env.SITE_BASE_URL || 'http://localhost:4321').replace(/\/$/, '');

console.log('=== Auditoria SEO (banco local) ===');
console.log('SITE_BASE_URL:', base);
console.log('URLs no sitemap (estimado):', total);
console.log('  Páginas estáticas:', STATIC);
console.log('  Vagas:', jobs);
console.log('  Posts:', posts);
console.log('  Cidades com vagas:', cities);
console.log('  Empresas:', companies);
console.log('');
console.log('Admin noindex: AdminLayout + /admin/login');
console.log('Robots deve bloquear: /admin, /api, /api/admin');

await prisma.$disconnect();
