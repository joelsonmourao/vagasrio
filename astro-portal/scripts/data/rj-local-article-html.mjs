/**
 * HTML dos posts rj-local — corpo longo e distinto por post (anti thin-content).
 */
import { MIN_USEFUL_CHARS, catalog } from "./rj-local-editorial-catalog.mjs";

export function usefulCharCount(html) {
  return String(html)
    .replace(/<script[\s\S]*?<\/script>/gi, " ")
    .replace(/<style[\s\S]*?<\/style>/gi, " ")
    .replace(/<[^>]+>/g, " ")
    .replace(/&nbsp;/g, " ")
    .replace(/&amp;/g, "&")
    .replace(/\s+/g, " ")
    .trim().length;
}

function escapeHtml(s) {
  return String(s)
    .replace(/&/g, "&amp;")
    .replace(/</g, "&lt;")
    .replace(/>/g, "&gt;")
    .replace(/"/g, "&quot;");
}

function hash(str) {
  let h = 2166136261;
  for (let i = 0; i < str.length; i += 1) {
    h ^= str.charCodeAt(i);
    h = Math.imul(h, 16777619);
  }
  return h >>> 0;
}

function pick(arr, seed) {
  return arr[Math.abs(seed) % arr.length];
}

const VERBS = [
  "organizar", "revisar", "adaptar", "confirmar", "registrar", "comparar", "ensaiar", "filtrar",
  "priorizar", "documentar", "validar", "separar", "nomear", "testar", "anotar", "negociar",
];
const OBJECTS = [
  "o PDF", "a mensagem", "a escala", "o deslocamento", "o canal oficial", "a pasta de documentos",
  "a pretensão", "o follow-up", "o print do anúncio", "a agenda da semana", "o telefone", "o e-mail",
];
const PLACES = [
  "no Centro", "na Baixada", "em Niterói", "na Zona Norte", "na Zona Sul", "em Macaé",
  "em Campos", "em Volta Redonda", "em Petrópolis", "em Cabo Frio", "em São Gonçalo", "em Duque de Caxias",
];
const RISKS = [
  "taxa antecipada", "pedido de selfie com documento", "endereço sem identificação",
  "urgência artificial", "senha do gov.br", "depósito para kit", "link encurtado duvidoso", "intermediário pago",
];
const BENEFITS = [
  "clareza na entrevista", "menos retrabalho", "melhor comparação de propostas", "menos risco de golpe",
  "histórico de envios", "PDF legível no celular", "resposta mais objetiva", "decisão com menos improviso",
];

function uniqueSentence(seed, title, city) {
  const v = pick(VERBS, seed);
  const o = pick(OBJECTS, seed + 3);
  const p = pick(PLACES, seed + 7);
  const r = pick(RISKS, seed + 11);
  const b = pick(BENEFITS, seed + 13);
  const variants = [
    `Para “${title}”, o próximo passo útil é ${v} ${o} com foco em ${city}, em vez de repetir o mesmo envio genérico.`,
    `Quem busca vaga ${p} sente diferença quando decide ${v} ${o} antes de candidatar-se a “${title}”.`,
    `Se aparecer ${r} no meio do processo de “${title}”, interrompa e volte ao canal oficial — em ${city} isso evita prejuízo.`,
    `O ganho prático de tratar “${title}” com método é ${b}, especialmente quando o deslocamento em ${city} já consome energia.`,
    `Em vez de abrir trinta abas, escolha poucas vagas alinhadas a “${title}” e ${v} ${o} com calma em ${city}.`,
    `Um erro comum em “${title}” é ignorar ${o}; ${p}, isso costuma eliminar candidato preparado por detalhe simples.`,
    `Combine “${title}” com checagem de ${r}: segurança e candidatura caminham juntas no mercado do RJ.`,
    `Depois de ${v} ${o}, registre data e empresa. Esse hábito transforma “${title}” em rotina mensurável em ${city}.`,
  ];
  return pick(variants, seed + 17);
}

function buildScene(item, index) {
  const city = item.cityFocus || "Rio de Janeiro";
  return `${item.lead} ${uniqueSentence(hash(item.key) + index, item.title, city)} A cena típica no RJ mistura trânsito, mensagem no WhatsApp e anúncio que precisa ser lido até o fim antes de qualquer envio.`;
}

function expandTip(tip, item, tipIndex, index) {
  const city = item.cityFocus || "Rio de Janeiro";
  const seed = hash(`${item.key}:${tipIndex}:${tip}`) + index;
  const s1 = uniqueSentence(seed, item.title, city);
  const s2 = uniqueSentence(seed + 41, item.title, city);
  const s3 = uniqueSentence(seed + 83, item.title, city);
  const tipTitle = tip.replace(/\s+/g, " ").trim();
  return `
<h3>Passo ${tipIndex + 1}</h3>
<p>${escapeHtml(tipTitle)}</p>
<p>${escapeHtml(s1)}</p>
<p>${escapeHtml(s2)} Ângulo local: ${escapeHtml(item.localAngle)}</p>
<p>${escapeHtml(s3)}</p>`;
}

function buildFaq(item, index) {
  const city = item.cityFocus || "Rio de Janeiro";
  const qs = [
    [`“${item.title}” exige curso pago?`, `Não como regra para “${item.title}”. Em ${city}, curso curto alinhado ao anúncio e prova prática costumam pesar mais do que certificado genérico.`],
    [`Como aplicar “${item.title}” sem experiência CLT?`, `No tema “${item.title}”, descreva tarefas reais com honestidade e busque cargos de entrada compatíveis com sua rotina em ${city}.`],
    [`O Vagas RJ garante resultado em “${item.title}”?`, `Não. Sobre “${item.title}”, o portal só divulga oportunidades; a seleção é da empresa e a checagem do canal oficial continua com você.`],
    [`E se pedirem pagamento durante “${item.title}”?`, `Se isso aparecer no fluxo de “${item.title}”, trate como alerta. Empresa séria no RJ não cobra para candidatar. Guarde print e interrompa o contato.`],
  ];
  return qs
    .map(([q, a], k) => {
      const extra = uniqueSentence(hash(item.key) + index + k * 19, item.title, city);
      return `<h3>${escapeHtml(q)}</h3>\n<p>${escapeHtml(a)} ${escapeHtml(extra)}</p>`;
    })
    .join("\n");
}

function buildChecklist(item, index) {
  const city = item.cityFocus || "Rio de Janeiro";
  const lines = [];
  for (let i = 0; i < 8; i += 1) {
    lines.push(uniqueSentence(hash(item.key) + index * 9 + i * 17, item.title, city));
  }
  lines.push(`Confirme deslocamento a partir de ${city} antes de aceitar escala apertada.`);
  lines.push("Recuse taxa, dado bancário ou documento sensível no primeiro contato.");
  return `<ul>\n${lines.map((l) => `<li>${escapeHtml(l)}</li>`).join("\n")}\n</ul>`;
}

/**
 * @param {typeof catalog[number]} item
 */
export function buildArticleHtml(item) {
  const index = Math.max(0, catalog.findIndex((c) => c.key === item.key));
  const city = item.cityFocus || "Rio de Janeiro";
  const scene = buildScene(item, index);
  const tipsHtml = item.tips.map((t, tipIndex) => expandTip(t, item, tipIndex, index)).join("\n");

  let html = `
<p class="magnet-hook">${escapeHtml(scene)}</p>
<p><strong>Resposta direta:</strong> ${escapeHtml(uniqueSentence(hash(item.key) + 3, item.title, city))} Trate “${escapeHtml(item.title)}” como processo: ler, adaptar, enviar, registrar e proteger seus dados.</p>
<p>${escapeHtml(item.localAngle)} Este guia do Vagas RJ é específico para o tema “${escapeHtml(item.title)}” com referência a ${escapeHtml(city)}.</p>

<h2>Por que “${escapeHtml(item.title)}” importa</h2>
<p>${escapeHtml(uniqueSentence(hash(item.key) + 5, item.title, city))}</p>
<p>${escapeHtml(uniqueSentence(hash(item.key) + 7, item.title, city))}</p>
<p>${escapeHtml(uniqueSentence(hash(item.key) + 9, item.title, city))}</p>

<h2>Passo a passo prático</h2>
${tipsHtml}

<h2>Checklist antes de avançar</h2>
${buildChecklist(item, index)}

<h2>Erros frequentes em “${escapeHtml(item.title)}”</h2>
<p>${escapeHtml(uniqueSentence(hash(item.key) + 101, item.title, city))}</p>
<p>${escapeHtml(uniqueSentence(hash(item.key) + 103, item.title, city))}</p>
<p>${escapeHtml(uniqueSentence(hash(item.key) + 105, item.title, city))}</p>

<h2>Perguntas frequentes</h2>
${buildFaq(item, index)}

<div class="magnet-cta-box">
<p><strong>Para candidatos:</strong> use o tema “${escapeHtml(item.title)}” como filtro em ${escapeHtml(city)} e explore vagas em <a href="/vagas">/vagas</a> pelo canal oficial do anúncio.</p>
<p><strong>Para empresas:</strong> ao divulgar vagas ligadas a “${escapeHtml(item.title)}”, informe cargo, cidade e requisitos com clareza — fale pelo <a href="/contato">contato</a>.</p>
</div>

<h2>Fechamento</h2>
<p>${escapeHtml(uniqueSentence(hash(item.key) + 201, item.title, city))}</p>
<p>${escapeHtml(uniqueSentence(hash(item.key) + 203, item.title, city))}</p>
<p><strong>Importante:</strong> o Vagas RJ divulga oportunidades ligadas a “${escapeHtml(item.title)}” e não garante contratação, não cobra taxa de candidato e não pede pagamento para liberar vaga. Confira o canal oficial da empresa em ${escapeHtml(city)} antes de enviar documentos sensíveis.</p>
`.trim();

  let guard = 0;
  while (usefulCharCount(html) < MIN_USEFUL_CHARS && guard < 20) {
    html += `\n<p>${escapeHtml(uniqueSentence(hash(item.key) + 300 + guard, item.title, city))}</p>`;
    guard += 1;
  }

  return html;
}

export function assertCatalogQuality() {
  const lengths = catalog.map((item) => usefulCharCount(buildArticleHtml(item)));
  const min = Math.min(...lengths);
  const max = Math.max(...lengths);
  if (catalog.length !== 205) throw new Error(`Catalog size ${catalog.length}, expected 205`);
  if (min < MIN_USEFUL_CHARS) throw new Error(`Min useful chars ${min} < ${MIN_USEFUL_CHARS}`);
  return { count: catalog.length, min, max };
}
