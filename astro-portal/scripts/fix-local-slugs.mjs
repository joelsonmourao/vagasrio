/**
 * Atualiza slugs no SQLite local para a forma corrigida (após gerar redirects).
 * node scripts/fix-local-slugs.mjs
 */
import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';
const __dirname = path.dirname(fileURLToPath(import.meta.url));
const root = path.join(__dirname, '..');

const ACCENT_MAP = {
  á: 'a', à: 'a', â: 'a', ã: 'a', ä: 'a',
  é: 'e', è: 'e', ê: 'e', ë: 'e',
  í: 'i', ì: 'i', î: 'i', ï: 'i',
  ó: 'o', ò: 'o', ô: 'o', õ: 'o', ö: 'o',
  ú: 'u', ù: 'u', û: 'u', ü: 'u',
  ç: 'c', ñ: 'n',
};

function slugify(value) {
  let s = value.trim().toLowerCase();
  for (const [from, to] of Object.entries(ACCENT_MAP)) {
    s = s.replaceAll(from, to);
  }
  s = s.normalize('NFD').replace(/\p{M}/gu, '');
  s = s.replace(/[^a-z0-9]+/g, '-').replace(/-+/g, '-').replace(/^-+|-+$/g, '');
  return s || 'item';
}

const seedPath = path.join(root, 'prisma/seed-data.json');
const seed = JSON.parse(fs.readFileSync(seedPath, 'utf8'));

function patchSlugs(items, getName, getSlugKey = () => 'slug') {
  for (const item of items) {
    const name = getName(item);
    const key = getSlugKey(item);
    const next = slugify(name);
    if (item[key] !== next) {
      item[key] = next;
    }
  }
}

for (const c of seed.cities) {
  const next = `${slugify(c.name)}-rj`;
  if (c.slug !== next) c.slug = next;
}

patchSlugs(seed.companies, (c) => c.name);
patchSlugs(seed.categories, (c) => c.name);

for (const j of seed.jobs) {
  const city = seed.cities.find((c) => c.id === j.city_id);
  j.slug = slugify(`${j.title}-${city?.name || ''}-${j.state || 'RJ'}`);
}

for (const p of seed.blog_posts) {
  p.slug = slugify(p.title);
}

fs.writeFileSync(seedPath, `${JSON.stringify(seed, null, 2)}\n`, 'utf8');
console.log('Updated prisma/seed-data.json slugs. Run: npm run db:seed:sqlite');
