import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import { normalizeSiteName } from '../src/lib/config';
import { getRjJobsFaqs } from '../src/lib/jobs-faq';
import { buildBaseSalary, buildJobPostingSchema, buildPublicJobPostingSchema, sanitizeJobDescriptionHtml } from '../src/lib/job-posting';
import { formatJobValidThroughBr } from '../src/lib/format';
import { paginatedPath, parsePageParam } from '../src/lib/pagination-pages';

assert.equal(
  normalizeSiteName('Vagas RJ RIO RIO'),
  'Vagas RJ RIO',
  'a marca não pode repetir o sufixo RIO',
);

assert.equal(parsePageParam('2'), 2, 'página válida deve ser preservada');
assert.equal(parsePageParam('abc'), 1, 'página inválida deve voltar para a primeira');
assert.equal(parsePageParam('-3'), 1, 'página negativa deve voltar para a primeira');
assert.equal(paginatedPath('/blog', 1), '/blog', 'página 1 deve manter URL limpa');
assert.equal(paginatedPath('/blog', 3), '/blog?page=3', 'página seguinte deve ter URL própria');

const emptyFaq = getRjJobsFaqs({
  brand: 'Vagas RJ RIO RIO',
  jobCount: 0,
  cities: ['Rio de Janeiro', 'Niterói'],
});
const emptyCopy = emptyFaq.map((item) => item.answer).join(' ');

assert.doesNotMatch(
  emptyCopy,
  /Vagas RJ RIO RIO/i,
  'o FAQ também deve normalizar a marca antes de renderizar conteúdo e schema',
);

assert.doesNotMatch(
  emptyCopy,
  /hoje há\s+vagas|há oportunidades/i,
  'o estado vazio não pode afirmar que existem vagas',
);
assert.match(
  emptyCopy,
  /não (?:exibe|há) vagas públicas ativas/i,
  'o estado vazio deve informar claramente que não há vagas públicas',
);

const oneJobFaq = getRjJobsFaqs({ brand: 'Vagas RJ RIO', jobCount: 1 });
assert.match(
  oneJobFaq[0]?.answer || '',
  /Hoje há 1 vaga pública no portal\./,
  'a concordância do contador singular precisa ser preservada',
);

const publishedAt = new Date('2026-10-08T09:00:00-03:00');
const jobWithoutOptionalFacts = {
  id: 1,
  title: 'Auxiliar administrativo',
  slug: 'auxiliar-administrativo-rio-de-janeiro',
  description: '<p>Descrição completa da oportunidade.</p>',
  publishedAt,
  validThrough: null,
  applyUrl: 'mailto:rh@example.net',
  employmentType: 'FULL_TIME',
  salary: null,
  company: { name: 'Empresa contratante', website: null, logo: null },
  city: { name: 'Rio de Janeiro' },
};

assert.equal(
  buildBaseSalary(null),
  null,
  'salário ausente não pode virar baseSalary zero',
);
assert.equal(
  formatJobValidThroughBr(null, publishedAt),
  'Não informada',
  'a página não pode exibir uma validade inventada',
);

const sanitizedDescription = sanitizeJobDescriptionHtml(
  '<p onclick="alert(1)">Resumo <strong>útil</strong></p><ul><li>Item</li></ul><br class="x"><script>alert(1)</script>',
);
assert.match(sanitizedDescription, /<p>Resumo útil<\/p>/, 'parágrafo seguro deve ser preservado sem atributos');
assert.match(sanitizedDescription, /<ul><li>Item<\/li><\/ul>/, 'lista segura deve ser preservada');
assert.match(sanitizedDescription, /<br>/, 'quebra de linha segura deve ser preservada');
assert.doesNotMatch(sanitizedDescription, /script|onclick|strong/i, 'marcação insegura ou não suportada deve ser removida');

assert.equal(
  buildPublicJobPostingSchema(jobWithoutOptionalFacts, false),
  undefined,
  'vaga indisponível não pode manter JobPosting no HTML',
);
assert.equal(
  buildPublicJobPostingSchema(jobWithoutOptionalFacts, true)?.['@type'],
  'JobPosting',
  'vaga pública deve continuar com JobPosting',
);

const schemaWithoutOptionalFacts = buildJobPostingSchema(jobWithoutOptionalFacts);
assert.equal(
  schemaWithoutOptionalFacts.description,
  '<p>Descrição completa da oportunidade.</p>',
  'JobPosting deve preservar a estrutura HTML segura da descrição',
);
assert.ok(
  !('baseSalary' in schemaWithoutOptionalFacts),
  'JobPosting sem salário real deve omitir baseSalary',
);
assert.ok(
  !('validThrough' in schemaWithoutOptionalFacts),
  'JobPosting sem validade informada deve omitir validThrough',
);
const hiringOrganization = schemaWithoutOptionalFacts.hiringOrganization as Record<string, unknown>;
assert.ok(
  !('logo' in hiringOrganization),
  'empresa sem logotipo real não pode receber a marca do portal como fallback',
);

const schemaWithRealLogo = buildJobPostingSchema({
  ...jobWithoutOptionalFacts,
  company: {
    ...jobWithoutOptionalFacts.company,
    logo: 'https://empresa.example.net/logo.png',
  },
});
assert.equal(
  (schemaWithRealLogo.hiringOrganization as Record<string, unknown>).logo,
  'https://empresa.example.net/logo.png',
  'logotipo real da empresa deve ser preservado',
);

const postalAddress = (
  schemaWithoutOptionalFacts.jobLocation as {
    address: Record<string, unknown>;
  }
).address;
assert.ok(
  !('streetAddress' in postalAddress),
  'JobPosting sem endereço real deve omitir streetAddress',
);

const realSalary = buildBaseSalary('R$ 2.000,00');
assert.equal(
  realSalary?.value.value,
  2000,
  'salário real informado deve continuar no schema',
);

const applicationPageSource = readFileSync(
  new URL('../src/pages/candidatura/[slug].astro', import.meta.url),
  'utf8',
);
assert.ok(
  applicationPageSource.includes('!channelOk || !jobIsPubliclyVisible(job)'),
  'candidatura não pode encaminhar vaga inativa, expirada ou sem canal válido',
);
assert.ok(
  applicationPageSource.includes('robots="noindex,follow"'),
  'página intermediária de candidatura deve permanecer fora do índice',
);

for (const [relativePath, canonicalSnippet] of [
  ['../src/pages/blog/index.astro', "paginatedPath('/blog', page)"],
  ['../src/pages/vagas/index.astro', "paginatedPath('/vagas', page)"],
] as const) {
  const paginatedPageSource = readFileSync(new URL(relativePath, import.meta.url), 'utf8');
  assert.ok(
    paginatedPageSource.includes(canonicalSnippet),
    'listagem paginada deve usar canonical próprio em cada página',
  );
  assert.ok(
    paginatedPageSource.includes('!pageIsValid'),
    'página fora do intervalo deve permanecer fora do índice',
  );
}

for (const [relativePath, canonicalSnippet, label] of [
  ['../src/pages/empresas/[slug].astro', "paginatedPath(`/empresas/${company.slug}`, page)", 'empresa'],
  ['../src/pages/vagas/cidade/[slug].astro', "paginatedPath(`/vagas/cidade/${city.slug}`, page)", 'cidade'],
  ['../src/pages/vagas/categoria/[slug].astro', "paginatedPath(`/vagas/categoria/${category.slug}`, page)", 'categoria'],
] as const) {
  const listingPageSource = readFileSync(new URL(relativePath, import.meta.url), 'utf8');
  assert.ok(
    listingPageSource.includes(canonicalSnippet),
    `${label} paginada deve usar canonical próprio em cada página`,
  );
  assert.ok(
    listingPageSource.includes('pageIsValid'),
    `${label} fora do intervalo deve permanecer fora do índice`,
  );
  assert.ok(
    listingPageSource.includes('hasUnsupportedQuery'),
    `${label} com parâmetro não suportado deve permanecer fora do índice`,
  );
}

const cityListingSource = readFileSync(
  new URL('../src/pages/vagas/cidade/[slug].astro', import.meta.url),
  'utf8',
);
assert.ok(
  cityListingSource.includes('No momento, não há vagas públicas ativas em'),
  'FAQ de cidade sem vagas deve declarar indisponibilidade de forma factual',
);
assert.ok(
  !cityListingSource.includes('answer: \`Sim.'),
  'FAQ de cidade não pode responder sim quando não há vagas públicas',
);

const sitemapSource = readFileSync(new URL('../src/lib/sitemap-data.ts', import.meta.url), 'utf8');
assert.ok(
  sitemapSource.includes('jobs: { some: jobWhereIndexable(includeIndexable) }'),
  'sitemap de empresas deve exigir ao menos uma vaga pública indexável',
);
assert.ok(
  sitemapSource.includes('fetchSitemapCompanies(includeIndexable)'),
  'fallback de schema do sitemap deve ser aplicado também às empresas',
);

const middlewareSource = readFileSync(new URL('../src/middleware.ts', import.meta.url), 'utf8');
const expectedSecurityHeaders = [
  ['X-Content-Type-Options', 'nosniff'],
  ['X-Frame-Options', 'SAMEORIGIN'],
  ['Referrer-Policy', 'strict-origin-when-cross-origin'],
  ['Permissions-Policy', 'camera=(), microphone=(), geolocation=()'],
] as const;

for (const [name, value] of expectedSecurityHeaders) {
  assert.ok(
    middlewareSource.includes(`response.headers.set('${name}', '${value}')`),
    `o middleware deve manter ${name}: ${value}`,
  );
}

console.log('public-copy: ok');
