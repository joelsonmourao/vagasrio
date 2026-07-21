/**
 * Remove posts rj-local que não estão no catálogo atual (slugs órfãos).
 *   node scripts/cleanup-rj-local-orphans.mjs
 *   node scripts/cleanup-rj-local-orphans.mjs --write
 */
import { PrismaClient } from "@prisma/client";
import { catalog, slugFor } from "./data/rj-local-editorial-catalog.mjs";

const write = process.argv.includes("--write");
const keep = new Set(catalog.map((item, i) => slugFor(item, i)));
const prisma = new PrismaClient();

const rj = await prisma.blogPost.findMany({
  where: { slug: { startsWith: "rj-local-" } },
  select: { id: true, slug: true },
});
const orphans = rj.filter((p) => !keep.has(p.slug));
console.log(JSON.stringify({ keep: keep.size, rj: rj.length, orphans: orphans.length, sample: orphans.slice(0, 5).map((o) => o.slug) }, null, 2));

if (write && orphans.length) {
  const result = await prisma.blogPost.deleteMany({ where: { id: { in: orphans.map((o) => o.id) } } });
  console.log(JSON.stringify({ deleted: result.count }, null, 2));
}

await prisma.$disconnect();
