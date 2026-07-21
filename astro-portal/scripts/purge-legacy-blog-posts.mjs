#!/usr/bin/env node
/**
 * Relatório + exclusão dos posts legados (não rj-local), que são templates rasos.
 *
 *   node scripts/purge-legacy-blog-posts.mjs
 *   node scripts/purge-legacy-blog-posts.mjs --write
 *   node scripts/purge-legacy-blog-posts.mjs --write --i-understand-production
 */
import { PrismaClient } from "@prisma/client";

const write = process.argv.includes("--write");
const allowProd = process.argv.includes("--i-understand-production");
const databaseUrl = process.env.DATABASE_URL || "";
const looksProd =
  /postgres|postgresql|vagasrj\.rio\.br|production|prod/i.test(databaseUrl) &&
  !/localhost|127\.0\.0\.1|file:/i.test(databaseUrl);

function plainLen(html) {
  return String(html || "")
    .replace(/<[^>]+>/g, " ")
    .replace(/\s+/g, " ")
    .trim().length;
}

const prisma = new PrismaClient();

try {
  if (write && looksProd && !allowProd) {
    console.error(
      JSON.stringify({
        error: "Recusou escrever em banco que parece produção. Use --i-understand-production.",
        databaseUrlHint: databaseUrl.slice(0, 40),
      }),
    );
    process.exit(1);
  }

  const all = await prisma.blogPost.findMany({
    select: {
      id: true,
      slug: true,
      title: true,
      content: true,
      isActive: true,
      isIndexable: true,
      publishedAt: true,
      featuredImage: true,
    },
  });
  const now = new Date();
  const rj = all.filter((p) => p.slug.startsWith("rj-local-"));
  const legacy = all.filter((p) => !p.slug.startsWith("rj-local-"));
  const rjPublic = rj.filter((p) => p.isActive && p.isIndexable && p.publishedAt <= now);
  const rjShort = rj.filter((p) => plainLen(p.content) < 3400);
  const legacyShort = legacy.filter((p) => plainLen(p.content) < 3400);

  const report = {
    total: all.length,
    validRjLocal: {
      count: rj.length,
      publicNow: rjPublic.length,
      shortUnder3400: rjShort.length,
      avgChars: rj.length ? Math.round(rj.reduce((s, p) => s + plainLen(p.content), 0) / rj.length) : 0,
      withCover: rj.filter((p) => p.featuredImage).length,
    },
    invalidLegacyTemplates: {
      count: legacy.length,
      stillActive: legacy.filter((p) => p.isActive).length,
      inactive: legacy.filter((p) => !p.isActive).length,
      shortUnder3400: legacyShort.length,
      avgChars: legacy.length
        ? Math.round(legacy.reduce((s, p) => s + plainLen(p.content), 0) / legacy.length)
        : 0,
      sampleTitles: legacy.slice(0, 5).map((p) => p.title),
    },
    action: write
      ? `DELETE ${legacy.length} legado(s)`
      : "dry-run (passe --write para excluir legados)",
  };
  console.log(JSON.stringify(report, null, 2));

  if (write && legacy.length) {
    const result = await prisma.blogPost.deleteMany({
      where: { id: { in: legacy.map((p) => p.id) } },
    });
    const remaining = await prisma.blogPost.count();
    console.log(JSON.stringify({ deleted: result.count, remaining }, null, 2));
  }
} finally {
  await prisma.$disconnect();
}
