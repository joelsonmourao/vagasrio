/**
 * Gera src/lib/legacy-slug-redirects.ts comparando slug antigo (bug) vs slug corrigido.
 * Execute: node scripts/generate-legacy-slug-redirects.mjs
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

/** Slug legado: hifeniza antes de remover acentos (gerava log-istica, niter-oi, etc.). */
function legacySlugifyBroken(value) {
  let s = value.trim().toLowerCase();
  s = s.replace(/[^a-z0-9]+/g, '-').replace(/-+/g, '-').replace(/^-+|-+$/g, '');
  return s || 'item';
}

function slugify(value) {
  let s = value.trim().toLowerCase();
  for (const [from, to] of Object.entries(ACCENT_MAP)) {
    s = s.replaceAll(from, to);
  }
  s = s.normalize('NFD').replace(/\p{M}/gu, '');
  s = s.replace(/[^a-z0-9]+/g, '-').replace(/-+/g, '-').replace(/^-+|-+$/g, '');
  return s || 'item';
}

function addRedirect(map, fromPath, toPath) {
  if (!fromPath || !toPath || fromPath === toPath) return;
  map.set(fromPath, toPath);
}

const seed = JSON.parse(fs.readFileSync(path.join(root, 'prisma/seed-data.json'), 'utf8'));
const overridesPath = path.join(root, 'prisma/legacy-slug-overrides.json');
const overrides = fs.existsSync(overridesPath)
  ? JSON.parse(fs.readFileSync(overridesPath, 'utf8'))
  : {};
const redirects = new Map();
for (const [from, to] of Object.entries(overrides)) {
  addRedirect(redirects, from, to);
}

for (const c of seed.cities || []) {
  const good = c.slug;
  const bad = `${legacySlugifyBroken(c.name)}-rj`;
  addRedirect(redirects, `/vagas/cidade/${bad}`, `/vagas/cidade/${good}`);
}

for (const co of seed.companies || []) {
  const good = co.slug;
  const bad = legacySlugifyBroken(co.name);
  addRedirect(redirects, `/empresas/${bad}`, `/empresas/${good}`);
}

for (const j of seed.jobs || []) {
  const city = seed.cities.find((x) => x.id === j.city_id);
  const good = j.slug;
  const bad = legacySlugifyBroken(`${j.title}-${city?.name || ''}-${j.state || 'RJ'}`);
  addRedirect(redirects, `/vagas/${bad}`, `/vagas/${good}`);
}

for (const p of seed.blog_posts || []) {
  const good = p.slug;
  const bad = legacySlugifyBroken(p.title);
  addRedirect(redirects, `/blog/${bad}`, `/blog/${good}`);
}

const entries = [...redirects.entries()].sort((a, b) => a[0].localeCompare(b[0]));
const lines = entries.map(([from, to]) => `  ${JSON.stringify(from)}: ${JSON.stringify(to)},`);

const out = `/** Gerado por scripts/generate-legacy-slug-redirects.mjs — não editar à mão. */
export const LEGACY_SLUG_REDIRECTS: Record<string, string> = {
${lines.join('\n')}
};

export function resolveLegacySlugRedirect(pathname: string): string | null {
  return LEGACY_SLUG_REDIRECTS[pathname] ?? null;
}
`;

fs.writeFileSync(path.join(root, 'src/lib/legacy-slug-redirects.ts'), out, 'utf8');
console.log(`Wrote ${entries.length} redirects to src/lib/legacy-slug-redirects.ts`);
