import { readFileSync, existsSync } from 'node:fs';
import { join, dirname } from 'node:path';
import { fileURLToPath } from 'node:url';
import { PrismaClient } from '@prisma/client';

const prisma = new PrismaClient();
const dir = dirname(fileURLToPath(import.meta.url));
const seedPath = join(dir, 'seed-data.json');

function parseDate(v: string | null | undefined): Date {
  if (!v) return new Date();
  const d = new Date(v);
  return Number.isNaN(d.getTime()) ? new Date() : d;
}

async function main() {
  if (!existsSync(seedPath)) {
    console.warn('seed-data.json ausente. Rode: npm run export:legacy');
    return;
  }

  const data = JSON.parse(readFileSync(seedPath, 'utf-8'));

  for (const c of data.companies ?? []) {
    await prisma.company.upsert({
      where: { slug: c.slug },
      update: {
        name: c.name,
        website: c.website,
        description: c.description,
        logo: c.logo ?? null,
      },
      create: {
        name: c.name,
        slug: c.slug,
        website: c.website,
        description: c.description,
        logo: c.logo ?? null,
        createdAt: parseDate(c.created_at),
      },
    });
  }

  for (const c of data.categories ?? []) {
    await prisma.category.upsert({
      where: { slug: c.slug },
      update: { name: c.name },
      create: { name: c.name, slug: c.slug, createdAt: parseDate(c.created_at) },
    });
  }

  for (const c of data.cities ?? []) {
    await prisma.city.upsert({
      where: { slug: c.slug },
      update: { name: c.name, state: c.state },
      create: { name: c.name, slug: c.slug, state: c.state, createdAt: parseDate(c.created_at) },
    });
  }

  for (const c of data.blog_categories ?? []) {
    await prisma.blogCategory.upsert({
      where: { slug: c.slug },
      update: {
        name: c.name,
        description: c.description,
        isActive: !!c.is_active,
      },
      create: {
        name: c.name,
        slug: c.slug,
        description: c.description,
        isActive: !!c.is_active,
        createdAt: parseDate(c.created_at),
        updatedAt: parseDate(c.updated_at),
      },
    });
  }

  const companyByOldId = new Map<number, number>();
  for (const c of data.companies ?? []) {
    const row = await prisma.company.findUnique({ where: { slug: c.slug } });
    if (row) companyByOldId.set(c.id, row.id);
  }
  const cityByOldId = new Map<number, number>();
  for (const c of data.cities ?? []) {
    const row = await prisma.city.findUnique({ where: { slug: c.slug } });
    if (row) cityByOldId.set(c.id, row.id);
  }
  const catByOldId = new Map<number, number>();
  for (const c of data.categories ?? []) {
    const row = await prisma.category.findUnique({ where: { slug: c.slug } });
    if (row) catByOldId.set(c.id, row.id);
  }
  const blogCatByOldId = new Map<number, number>();
  for (const c of data.blog_categories ?? []) {
    const row = await prisma.blogCategory.findUnique({ where: { slug: c.slug } });
    if (row) blogCatByOldId.set(c.id, row.id);
  }

  for (const j of data.jobs ?? []) {
    const companyId = companyByOldId.get(j.company_id);
    const cityId = cityByOldId.get(j.city_id);
    if (!companyId || !cityId) continue;
    const categoryId = j.category_id ? catByOldId.get(j.category_id) ?? null : null;

    await prisma.job.upsert({
      where: { slug: j.slug },
      update: {
        title: j.title,
        description: j.description,
        isActive: !!j.is_active,
        applyUrl: j.apply_url,
        salary: j.salary,
        employmentType: j.employment_type,
        publishedAt: parseDate(j.published_at),
        validThrough: j.valid_through ? parseDate(j.valid_through) : null,
      },
      create: {
        title: j.title,
        slug: j.slug,
        companyId,
        cityId,
        categoryId,
        state: j.state,
        description: j.description,
        salary: j.salary,
        employmentType: j.employment_type,
        applyUrl: j.apply_url,
        isActive: !!j.is_active,
        publishedAt: parseDate(j.published_at),
        validThrough: j.valid_through ? parseDate(j.valid_through) : null,
        isDemo: !!j.is_demo,
        createdAt: parseDate(j.created_at),
        updatedAt: parseDate(j.updated_at),
      },
    });
  }

  for (const p of data.blog_posts ?? []) {
    const categoryId = blogCatByOldId.get(p.category_id);
    if (!categoryId) continue;
    await prisma.blogPost.upsert({
      where: { slug: p.slug },
      update: {
        title: p.title,
        excerpt: p.excerpt,
        content: p.content,
        seoTitle: p.seo_title,
        seoDescription: p.seo_description,
        isActive: !!p.is_active,
        publishedAt: parseDate(p.published_at),
        categoryId,
      },
      create: {
        title: p.title,
        slug: p.slug,
        categoryId,
        excerpt: p.excerpt,
        content: p.content,
        seoTitle: p.seo_title,
        seoDescription: p.seo_description,
        isActive: !!p.is_active,
        publishedAt: parseDate(p.published_at),
        createdAt: parseDate(p.created_at),
        updatedAt: parseDate(p.updated_at),
      },
    });
  }

  console.log('Seed concluído.');
}

main()
  .catch((e) => {
    console.error(e);
    process.exit(1);
  })
  .finally(() => prisma.$disconnect());
