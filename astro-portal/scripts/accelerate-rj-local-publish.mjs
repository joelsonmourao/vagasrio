#!/usr/bin/env node
/**
 * Acelera a agenda dos posts rj-local: publica todos agora,
 * com datas espalhadas nos últimos N dias (sinal de publicação humana).
 *
 *   node scripts/accelerate-rj-local-publish.mjs
 *   node scripts/accelerate-rj-local-publish.mjs --write
 *   node scripts/accelerate-rj-local-publish.mjs --write --i-understand-production
 *   node scripts/accelerate-rj-local-publish.mjs --write --days=10
 */
import { PrismaClient } from "@prisma/client";

const write = process.argv.includes("--write");
const allowProd = process.argv.includes("--i-understand-production");
const daysArg = process.argv.find((a) => a.startsWith("--days="));
const spreadDays = Math.max(3, Math.min(30, Number(daysArg?.split("=")[1] || 14)));

const databaseUrl = process.env.DATABASE_URL || "";
const looksProd =
  /postgres|postgresql|vagasrj\.rio\.br|production|prod/i.test(databaseUrl) &&
  !/localhost|127\.0\.0\.1|file:/i.test(databaseUrl);

const prisma = new PrismaClient();

function spreadPublishedAt(index, total, days) {
  const now = Date.now();
  const spanMs = days * 24 * 60 * 60 * 1000;
  const t = total <= 1 ? 0 : index / (total - 1);
  // Mais recentes no fim da lista (últimos do catálogo um pouco mais novos)
  const offset = Math.round((1 - t) * spanMs);
  const hourJitter = (index % 9) * 60 * 60 * 1000;
  return new Date(now - offset - hourJitter);
}

try {
  if (write && looksProd && !allowProd) {
    console.error(
      JSON.stringify({
        error: "Recusou escrever em banco que parece produção. Use --i-understand-production.",
      }),
    );
    process.exit(1);
  }

  const posts = await prisma.blogPost.findMany({
    where: { slug: { startsWith: "rj-local-" } },
    select: { id: true, slug: true, publishedAt: true, isActive: true, isIndexable: true },
    orderBy: { slug: "asc" },
  });

  const now = new Date();
  const future = posts.filter((p) => p.publishedAt > now);
  const report = {
    totalRjLocal: posts.length,
    alreadyPublic: posts.filter((p) => p.isActive && p.isIndexable && p.publishedAt <= now).length,
    scheduledFuture: future.length,
    spreadDays,
    action: write
      ? `UPDATE ${posts.length} posts → publicados (datas nos últimos ${spreadDays} dias)`
      : "dry-run (passe --write para aplicar)",
  };
  console.log(JSON.stringify(report, null, 2));

  if (write && posts.length) {
    let updated = 0;
    for (let i = 0; i < posts.length; i += 1) {
      const publishedAt = spreadPublishedAt(i, posts.length, spreadDays);
      await prisma.blogPost.update({
        where: { id: posts[i].id },
        data: {
          publishedAt,
          isActive: true,
          isIndexable: true,
        },
      });
      updated += 1;
    }
    const publicNow = await prisma.blogPost.count({
      where: {
        slug: { startsWith: "rj-local-" },
        isActive: true,
        isIndexable: true,
        publishedAt: { lte: new Date() },
      },
    });
    console.log(JSON.stringify({ updated, publicNow }, null, 2));
  }
} finally {
  await prisma.$disconnect();
}
