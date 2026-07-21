#!/usr/bin/env node
/**
 * Auditoria de qualidade do blog (AdSense / thin content).
 *   node scripts/audit-blog-quality.mjs
 *   node scripts/audit-blog-quality.mjs --from-catalog
 */
import { PrismaClient } from "@prisma/client";
import { catalog, MIN_USEFUL_CHARS, SLUG_BASE, slugFor } from "./data/rj-local-editorial-catalog.mjs";
import { buildArticleHtml, usefulCharCount } from "./data/rj-local-article-html.mjs";

const fromCatalog = process.argv.includes("--from-catalog");

function paragraphFreq(html) {
  const paras = [...String(html).matchAll(/<p>(.*?)<\/p>/gs)].map((m) =>
    m[1].replace(/<[^>]+>/g, "").replace(/\s+/g, " ").trim().slice(0, 140),
  );
  const map = new Map();
  for (const p of paras) {
    if (p.length < 40) continue;
    map.set(p, (map.get(p) || 0) + 1);
  }
  return [...map.entries()].sort((a, b) => b[1] - a[1]);
}

async function main() {
  if (fromCatalog) {
    const short = [];
    const allParas = new Map();
    for (let i = 0; i < catalog.length; i += 1) {
      const html = buildArticleHtml(catalog[i]);
      const n = usefulCharCount(html);
      if (n < MIN_USEFUL_CHARS) short.push({ slug: slugFor(catalog[i], i), n });
      for (const [p, c] of paragraphFreq(html)) {
        allParas.set(p, (allParas.get(p) || 0) + c);
      }
    }
    const topDup = [...allParas.entries()].sort((a, b) => b[1] - a[1]).slice(0, 10);
    const report = {
      source: "catalog",
      posts: catalog.length,
      short,
      topDuplicatedParagraphs: topDup.map(([t, c]) => ({ count: c, sample: t })),
    };
    console.log(JSON.stringify(report, null, 2));
    if (short.length) process.exit(1);
    return;
  }

  const prisma = new PrismaClient();
  try {
    const posts = await prisma.blogPost.findMany({
      select: {
        slug: true,
        title: true,
        content: true,
        seoTitle: true,
        featuredImage: true,
        isActive: true,
        isIndexable: true,
        publishedAt: true,
      },
    });
    const now = new Date();
    const rj = posts.filter((p) => p.slug.startsWith(`${SLUG_BASE}-`));
    const oldActive = posts.filter((p) => !p.slug.startsWith(`${SLUG_BASE}-`) && p.isActive);
    const short = [];
    const missingCover = [];
    const seoEllipsis = [];
    for (const p of rj) {
      const n = usefulCharCount(p.content);
      if (n < MIN_USEFUL_CHARS) short.push({ slug: p.slug, n });
      if (!p.featuredImage) missingCover.push(p.slug);
      if ((p.seoTitle || "").includes("...")) seoEllipsis.push(p.slug);
    }
    const publishedPublic = rj.filter((p) => p.isActive && p.isIndexable && p.publishedAt <= now);
    const report = {
      total: posts.length,
      rjLocal: rj.length,
      oldStillActive: oldActive.length,
      publishedPublic: publishedPublic.length,
      short,
      missingCover: missingCover.length,
      seoEllipsis: seoEllipsis.length,
      sampleOldActive: oldActive.slice(0, 5).map((p) => p.slug),
    };
    console.log(JSON.stringify(report, null, 2));
    if (short.length || oldActive.length > 0) process.exit(1);
  } finally {
    await prisma.$disconnect();
  }
}

main().catch((e) => {
  console.error(e);
  process.exit(1);
});
