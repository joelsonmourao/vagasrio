import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import { normalizeSiteName } from '../src/lib/config';
import { getRjJobsFaqs } from '../src/lib/jobs-faq';
import { buildBaseSalary, buildJobPostingSchema } from '../src/lib/job-posting';
import { formatJobValidThroughBr } from '../src/lib/format';

assert.equal(
  normalizeSiteName('Vagas RJ RIO RIO'),
  'Vagas RJ RIO',
  'a marca não pode repetir o sufixo RIO',
);

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

const schemaWithoutOptionalFacts = buildJobPostingSchema(jobWithoutOptionalFacts);
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
