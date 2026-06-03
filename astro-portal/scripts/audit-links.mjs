/**
 * Auditoria HTTP de rotas e URLs do sitemap.
 * Requer servidor rodando: npm run dev:sqlite (ou preview na porta configurada).
 *
 * Uso: npm run audit:links
 *      SITE_BASE_URL=http://localhost:4321 npm run audit:links
 */
import { readFileSync } from 'node:fs';
import { dirname, join } from 'node:path';
import { fileURLToPath } from 'node:url';

const root = join(dirname(fileURLToPath(import.meta.url)), '..');
const BASE = (process.env.SITE_BASE_URL || 'http://localhost:4321').replace(/\/$/, '');
const TIMEOUT_MS = 15000;

const MANUAL_PATHS = [
  '/',
  '/vagas',
  '/vagas/estado/rj',
  '/empresas',
  '/blog',
  '/sobre',
  '/contato',
  '/politica-de-privacidade',
  '/politica-de-cookies',
  '/termos-de-uso',
  '/ads.txt',
  '/robots.txt',
  '/sitemap.xml',
  '/sitemap-pages.xml',
  '/admin/login',
];

const LEGACY_FROM_PATHS = new Set(
  [
    ['/blog/como-montar-um-curr-iculo-simples-para-vagas-no-rj', '/blog/como-montar-um-curriculo-simples-para-vagas-no-rj'],
    ['/blog/o-que-colocar-no-curr-iculo-quando-n-ao-tenho-experi-encia', '/blog/o-que-colocar-no-curriculo-quando-nao-tenho-experiencia'],
    ['/vagas/auxiliar-de-log-istica-duque-de-caxias-rj', '/vagas/auxiliar-de-logistica-duque-de-caxias-rj'],
    ['/vagas/atendente-comercial-niter-oi-rj', '/vagas/atendente-comercial-niteroi-rj'],
    ['/vagas/operador-de-loja-s-ao-goncalo-rj', '/vagas/operador-de-loja-sao-goncalo-rj'],
  ].map(([f]) => f),
);

const LEGACY_SAMPLES = [
  ['/blog/como-montar-um-curr-iculo-simples-para-vagas-no-rj', '/blog/como-montar-um-curriculo-simples-para-vagas-no-rj'],
  ['/blog/o-que-colocar-no-curr-iculo-quando-n-ao-tenho-experi-encia', '/blog/o-que-colocar-no-curriculo-quando-nao-tenho-experiencia'],
  ['/vagas/auxiliar-de-log-istica-duque-de-caxias-rj', '/vagas/auxiliar-de-logistica-duque-de-caxias-rj'],
  ['/vagas/atendente-comercial-niter-oi-rj', '/vagas/atendente-comercial-niteroi-rj'],
  ['/vagas/operador-de-loja-s-ao-goncalo-rj', '/vagas/operador-de-loja-sao-goncalo-rj'],
];

function loadLegacyRedirects() {
  const file = join(root, 'src/lib/legacy-slug-redirects.ts');
  const src = readFileSync(file, 'utf8');
  const map = new Map();
  for (const m of src.matchAll(/"(\/[^"]+)":\s*"(\/[^"]+)"/g)) {
    map.set(m[1], m[2]);
  }
  return map;
}

function parseLocs(xml) {
  return [...xml.matchAll(/<loc>([^<]+)<\/loc>/gi)].map((m) => m[1].trim());
}

async function fetchStatus(url, follow = false) {
  const controller = new AbortController();
  const timer = setTimeout(() => controller.abort(), TIMEOUT_MS);
  try {
    const res = await fetch(url, {
      redirect: follow ? 'follow' : 'manual',
      signal: controller.signal,
      headers: { 'User-Agent': 'VagasRJ-LinkAudit/1.0' },
    });
    clearTimeout(timer);
    return {
      status: res.status,
      finalUrl: res.url,
      ok: res.ok,
      location: res.headers.get('location'),
    };
  } catch (err) {
    clearTimeout(timer);
    return { status: 0, error: err instanceof Error ? err.message : String(err) };
  }
}

async function checkUrl(pathOrUrl, opts = {}) {
  const url = pathOrUrl.startsWith('http') ? pathOrUrl : `${BASE}${pathOrUrl.startsWith('/') ? '' : '/'}${pathOrUrl}`;
  const first = await fetchStatus(url, false);
  const entry = { url, ...first };

  if (opts.expectRedirect) {
    entry.ok = first.status === 301 || first.status === 302 || first.status === 307 || first.status === 308;
    if (first.status >= 300 && first.status < 400) {
      const loc = first.headers?.get?.('location');
      entry.location = loc;
    }
    return entry;
  }

  if (opts.followRedirects) {
    const followed = await fetchStatus(url, true);
    entry.status = followed.status;
    entry.finalUrl = followed.finalUrl;
    entry.ok = followed.status >= 200 && followed.status < 400;
    return entry;
  }

  if (first.status >= 300 && first.status < 400 && !opts.allowRedirect) {
    entry.ok = true;
    entry.note = `redirect ${first.status} → ${first.location || '?'}`;
    entry.status = first.status;
  } else {
    entry.ok = first.status >= 200 && first.status < 400;
  }

  return entry;
}

async function collectSitemapUrls() {
  const indexRes = await fetch(`${BASE}/sitemap.xml`, { signal: AbortSignal.timeout(TIMEOUT_MS) });
  if (!indexRes.ok) throw new Error(`sitemap.xml retornou ${indexRes.status}`);
  const indexXml = await indexRes.text();
  const childSitemaps = parseLocs(indexXml).filter((u) => u.includes('sitemap-') && u.endsWith('.xml'));

  const urls = new Set();
  for (const smUrl of childSitemaps) {
    try {
      const res = await fetch(smUrl, { signal: AbortSignal.timeout(TIMEOUT_MS) });
      if (res.status === 404) continue;
      const xml = await res.text();
      for (const loc of parseLocs(xml)) urls.add(loc);
    } catch {
      /* skip */
    }
  }
  return { childSitemaps, urls: [...urls] };
}

async function crawlInternalLinks(seedPaths, legacyFromPaths = new Set()) {
  const toVisit = [...seedPaths];
  const visited = new Set();
  const broken = [];
  const found = new Set();

  while (toVisit.length > 0 && visited.size < 80) {
    const path = toVisit.shift();
    if (!path || visited.has(path)) continue;
    visited.add(path);

    const res = await fetch(`${BASE}${path}`, { signal: AbortSignal.timeout(TIMEOUT_MS) });
    if (!res.ok) {
      broken.push({ url: `${BASE}${path}`, status: res.status, source: 'crawl-seed' });
      continue;
    }
    const html = await res.text();
    const hrefs = [...html.matchAll(/\shref=["']([^"'#]+)["']/gi)].map((m) => m[1].trim());

    for (let href of hrefs) {
      if (href.startsWith('mailto:') || href.startsWith('tel:')) continue;
      if (href.startsWith('http') && !href.startsWith(BASE)) continue;
      if (href.startsWith(BASE)) href = href.slice(BASE.length);
      if (!href.startsWith('/')) continue;
      if (href.startsWith('/admin') || href.startsWith('/api')) continue;
      if (legacyFromPaths.has(href)) continue;
      found.add(href);
      if (!visited.has(href) && !toVisit.includes(href)) toVisit.push(href);
    }
  }

  for (const href of found) {
    const check = await checkUrl(href, { followRedirects: true });
    if (!check.ok && check.status !== 0) {
      broken.push({ url: check.url, status: check.status, source: 'crawl-internal' });
    }
  }

  return { visited: visited.size, linksFound: found.size, broken };
}

async function main() {
  console.log(`\n🔗 Auditoria de links — ${BASE}\n`);

  const results = [];
  let ok = 0;
  let redirects = 0;
  let errors = 0;

  try {
    await fetch(`${BASE}/`, { signal: AbortSignal.timeout(5000) });
  } catch {
    console.error(`❌ Servidor inacessível em ${BASE}`);
    console.error('   Inicie: npm run dev:sqlite');
    console.error('   Ou após build: npm run build:sqlite && npm run preview:sqlite\n');
    process.exit(2);
  }

  for (const path of MANUAL_PATHS) {
    const r = await checkUrl(path);
    results.push({ type: 'manual', ...r });
    if (r.ok) ok += 1;
    else if (r.status >= 300 && r.status < 400) redirects += 1;
    else errors += 1;
  }

  let sitemapUrls = [];
  try {
    const sm = await collectSitemapUrls();
    sitemapUrls = sm.urls;
    console.log(`Sitemap: ${sm.childSitemaps.length} arquivo(s), ${sitemapUrls.length} URL(s)\n`);
    for (const path of sm.childSitemaps.map((u) => u.replace(BASE, ''))) {
      const r = await checkUrl(path);
      results.push({ type: 'sitemap-file', ...r });
      if (r.ok || r.status === 200) ok += 1;
      else errors += 1;
    }
  } catch (e) {
    console.warn('Aviso: não foi possível ler sitemap completo:', e.message);
  }

  for (const url of sitemapUrls) {
    const path = url.replace(BASE, '');
    const r = await checkUrl(path, { followRedirects: true });
    results.push({ type: 'sitemap-url', ...r });
    if (r.ok) ok += 1;
    else if (r.status >= 300 && r.status < 400) redirects += 1;
    else errors += 1;
  }

  loadLegacyRedirects();
  for (const [from, to] of LEGACY_SAMPLES) {
    const raw = await fetchStatus(`${BASE}${from}`, false);
    const r = { type: 'legacy', url: `${BASE}${from}`, expected: to, ...raw };
    results.push(r);
    if (raw.status === 301 || raw.status === 308) {
      redirects += 1;
      const loc = raw.location || '';
      if (loc.includes(to) || loc.endsWith(to)) ok += 1;
      else {
        r.warn = `Location: ${loc}`;
        errors += 1;
      }
    } else if (raw.status === 200) {
      r.warn = 'URL antiga ainda retorna 200 (deveria ser 301)';
      errors += 1;
    } else {
      errors += 1;
    }
  }

  const crawl = await crawlInternalLinks(['/', '/vagas', '/blog', '/empresas', '/sobre'], LEGACY_FROM_PATHS);
  for (const b of crawl.broken) {
    results.push({ type: 'crawl', url: b.url, status: b.status, ok: false, source: b.source });
    errors += 1;
  }

  const failed = results.filter(
    (r) => !r.ok && r.type !== 'legacy' && !(r.status >= 300 && r.status < 400 && r.note?.startsWith('redirect')),
  );
  const failedLegacy = results.filter((r) => r.type === 'legacy' && (r.status !== 301 && r.status !== 308));

  console.log('── Resumo ──');
  console.log(`Testes registrados: ${results.length}`);
  console.log(`OK (2xx):           ${ok}`);
  console.log(`Redirects (3xx):    ${redirects}`);
  console.log(`Falhas 4xx/5xx:     ${failed.length + failedLegacy.length}`);
  console.log(`Crawl: ${crawl.visited} páginas, ${crawl.linksFound} links internos\n`);

  if (failed.length > 0 || failedLegacy.length > 0) {
    console.log('── URLs com problema ──');
    for (const f of [...failed, ...failedLegacy].slice(0, 50)) {
      console.log(`  [${f.status || 'ERR'}] ${f.url || f.path} ${f.note || ''} ${f.warn || ''}`);
    }
    if (failed.length + failedLegacy.length > 50) {
      console.log(`  ... e mais ${failed.length + failedLegacy.length - 50}`);
    }
    console.log('');
    process.exit(1);
  }

  console.log('✅ Nenhum 404/500 nas rotas auditadas.\n');
}

main().catch((err) => {
  console.error(err);
  process.exit(1);
});
