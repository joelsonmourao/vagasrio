#!/usr/bin/env node
/**
 * Seed editorial RJ (205 posts) + desativa templates antigos.
 *
 *   node scripts/seed-rj-local-editorial.mjs
 *   node scripts/seed-rj-local-editorial.mjs --write
 *   Produção: RJ_EDITORIAL_ALLOW_PRODUCTION=1 ... --write --i-understand-production
 */
import { PrismaClient } from "@prisma/client";
import { catalog, MIN_USEFUL_CHARS, SLUG_BASE, slugFor } from "./data/rj-local-editorial-catalog.mjs";
import { assertCatalogQuality, buildArticleHtml, usefulCharCount } from "./data/rj-local-article-html.mjs";

const write = process.argv.includes("--write");
const understandProduction = process.argv.includes("--i-understand-production");
const allowProduction = process.env.RJ_EDITORIAL_ALLOW_PRODUCTION === "1";
const databaseUrl = process.env.DATABASE_URL || process.env.DATABASE_URL_SQLITE || "";

function describeDbUrl(url) {
  if (!url) return { error: "DATABASE_URL ausente", host: "", database: "", port: "" };
  if (url.startsWith("file:")) {
    return { host: "file", port: "", database: url.replace(/^file:/, ""), user: "" };
  }
  try {
    const parsed = new URL(url);
    return {
      host: parsed.hostname,
      port: parsed.port || "(default)",
      database: parsed.pathname.replace(/^\//, ""),
      user: parsed.username || "(none)",
    };
  } catch {
    return { error: "URL inválida", host: "", database: "", port: "" };
  }
}

const target = describeDbUrl(databaseUrl);
console.log(JSON.stringify({ mode: write ? "write" : "dry-run", target }, null, 2));

if (target.error && write) {
  console.error(target.error);
  process.exit(1);
}

const hostIsLocal = target.host === "127.0.0.1" || target.host === "localhost" || target.host === "file";
const hostLooksDockerInternal =
  !hostIsLocal && target.host && !String(target.host).includes(".") && /^[a-z0-9]{8,}$/i.test(String(target.host));
const looksProduction =
  /vagasrj\.rio\.br|production|prod/i.test(databaseUrl) ||
  process.env.APP_ENV === "production" ||
  hostLooksDockerInternal;

if (write && looksProduction && !(allowProduction && understandProduction)) {
  console.error(
    "Recusa fail-closed: alvo parece produção. Defina RJ_EDITORIAL_ALLOW_PRODUCTION=1 e --i-understand-production.",
  );
  process.exit(1);
}

const quality = assertCatalogQuality();
console.log(JSON.stringify({ catalogQuality: quality }, null, 2));

/** 3 horários/dia BRT a partir de amanhã */
function buildScheduleSlots(count, from = new Date()) {
  const hoursBrt = [9, 13, 17];
  const slots = [];
  const cursor = new Date(from);
  cursor.setUTCHours(12, 0, 0, 0);
  cursor.setUTCDate(cursor.getUTCDate() + 1);
  while (slots.length < count) {
    const day = cursor.getUTCDay();
    if (day !== 0 && day !== 6) {
      for (const hour of hoursBrt) {
        if (slots.length >= count) break;
        const slot = new Date(Date.UTC(cursor.getUTCFullYear(), cursor.getUTCMonth(), cursor.getUTCDate(), hour + 3, 0, 0));
        slots.push(slot);
      }
    }
    cursor.setUTCDate(cursor.getUTCDate() + 1);
  }
  return slots;
}

const PILOT = 45;
const scheduleSlots = buildScheduleSlots(Math.max(0, catalog.length - PILOT));

function seoTitle(title) {
  const base = `${title} | Dicas de Emprego | Vagas RJ RIO`;
  return base.length <= 60 ? base : `${title.slice(0, 40).trim()} | Vagas RJ RIO`.slice(0, 60);
}

function seoDescription(item) {
  const raw = `${item.lead} Portal Vagas RJ RIO — emprego no Rio de Janeiro.`.replace(/\s+/g, " ").trim();
  return raw.length <= 160 ? raw : `${raw.slice(0, 157)}...`;
}

async function main() {
  const lengths = catalog.map((item) => usefulCharCount(buildArticleHtml(item)));
  const summary = {
    posts: catalog.length,
    minChars: Math.min(...lengths),
    maxChars: Math.max(...lengths),
    pilot: PILOT,
    scheduled: catalog.length - PILOT,
    minRequired: MIN_USEFUL_CHARS,
  };

  if (!write) {
    console.log(JSON.stringify({ ok: true, dryRun: true, summary }, null, 2));
    return;
  }

  const prisma = new PrismaClient();
  try {
    const cats = await prisma.blogCategory.findMany();
    const catBySlug = new Map(cats.map((c) => [c.slug, c.id]));

    const deactivated = await prisma.blogPost.updateMany({
      where: { NOT: { slug: { startsWith: `${SLUG_BASE}-` } } },
      data: { isActive: false, isIndexable: false },
    });

    let upserted = 0;
    const now = new Date();
    for (let i = 0; i < catalog.length; i += 1) {
      const item = catalog[i];
      const slug = slugFor(item, i);
      const categoryId = catBySlug.get(item.categorySlug);
      if (!categoryId) {
        throw new Error(`Categoria ausente: ${item.categorySlug}`);
      }
      const content = buildArticleHtml(item);
      const chars = usefulCharCount(content);
      if (chars < MIN_USEFUL_CHARS) {
        throw new Error(`Post ${slug} com ${chars} chars`);
      }
      const isPilot = i < PILOT;
      const publishedAt = isPilot ? now : scheduleSlots[i - PILOT];
      const featuredImage = `/assets/img/covers/rj-local/${slug}.svg`;
      const excerpt = item.lead.length > 220 ? `${item.lead.slice(0, 217)}...` : item.lead;

      await prisma.blogPost.upsert({
        where: { slug },
        update: {
          title: item.title,
          excerpt,
          content,
          seoTitle: seoTitle(item.title),
          seoDescription: seoDescription(item),
          featuredImage,
          isActive: true,
          isIndexable: true,
          publishedAt,
          categoryId,
        },
        create: {
          slug,
          title: item.title,
          excerpt,
          content,
          seoTitle: seoTitle(item.title),
          seoDescription: seoDescription(item),
          featuredImage,
          isActive: true,
          isIndexable: true,
          publishedAt,
          categoryId,
        },
      });
      upserted += 1;
    }

    console.log(
      JSON.stringify(
        {
          ok: true,
          write: true,
          deactivatedOld: deactivated.count,
          upserted,
          summary,
        },
        null,
        2,
      ),
    );
  } finally {
    await prisma.$disconnect();
  }
}

main().catch((err) => {
  console.error(err);
  process.exit(1);
});
