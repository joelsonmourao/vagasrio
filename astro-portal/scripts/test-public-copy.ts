import assert from 'node:assert/strict';
import { normalizeSiteName } from '../src/lib/config';
import { getRjJobsFaqs } from '../src/lib/jobs-faq';

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

console.log('public-copy: ok');
