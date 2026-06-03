import { PrismaClient } from '@prisma/client';

process.env.DATABASE_URL = process.env.DATABASE_URL || 'file:./dev.sqlite';

const prisma = new PrismaClient();

const jobWhere = { isActive: true, isIndexable: true };
const postWhere = { isActive: true, isIndexable: true };

const [jobs, posts, cities, companies] = await Promise.all([
  prisma.job.findMany({ where: jobWhere, select: { slug: true, updatedAt: true } }),
  prisma.blogPost.findMany({ where: postWhere, select: { slug: true, updatedAt: true } }),
  prisma.city.findMany({ select: { slug: true } }),
  prisma.company.findMany({ select: { slug: true } }),
]);

const staticPaths = ['/', '/vagas', '/empresas', '/blog', '/sobre', '/contato', '/politica-de-privacidade', '/politica-de-cookies', '/termos-de-uso', '/vagas/estado/rj'];

const urls = [
  ...staticPaths,
  ...jobs.map((j) => `/vagas/${j.slug}`),
  ...cities.map((c) => `/vagas/cidade/${c.slug}`),
  ...companies.map((c) => `/empresas/${c.slug}`),
  ...posts.map((p) => `/blog/${p.slug}`),
];

console.log('Total URLs:', urls.length);
console.log('Jobs:', jobs.length);
console.log('Posts:', posts.length);
console.log('Cities:', cities.length);
console.log('Companies:', companies.length);
console.log('Static:', staticPaths.length);

await prisma.$disconnect();
