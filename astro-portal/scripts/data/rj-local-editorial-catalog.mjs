/**
 * Catálogo editorial RJ — 205 pautas locais (Vagas RJ).
 * Conteúdo gerado de forma determinística para evitar clones entre posts.
 */
export const MIN_USEFUL_CHARS = 3400;
export const SLUG_BASE = "rj-local";

const CITIES = [
  "Rio de Janeiro",
  "Niterói",
  "São Gonçalo",
  "Duque de Caxias",
  "Nova Iguaçu",
  "Petrópolis",
  "Volta Redonda",
  "Campos dos Goytacazes",
  "Cabo Frio",
  "Macaé",
  "Itaboraí",
  "Belford Roxo",
];

const CATEGORIES = [
  "curriculo",
  "entrevista-de-emprego",
  "primeiro-emprego",
  "mercado-de-trabalho-no-rj",
  "seguranca-para-candidatos",
  "direitos-e-cuidados-no-trabalho",
  "carreira-e-desenvolvimento",
  "vagas-por-cidade",
  "profissoes-e-areas",
  "jovem-aprendiz-e-estagio",
  "dicas-para-candidatura",
];

/** @type {Record<string, string[]>} */
const TOPIC_BANK = {
  curriculo: [
    "Currículo ATS para triagem automática no RJ",
    "Como adaptar o PDF a cada vaga no Rio",
    "Erros que fazem o currículo sumir no celular do recrutador",
    "Experiência informal no currículo sem inventar cargo",
    "Foto no currículo: quando ajuda e quando atrapalha",
    "Objetivo profissional curto e útil para o mercado fluminense",
    "Como listar cursos sem parecer enchimento",
    "Currículo para auxiliar administrativo na capital",
    "Currículo para atendimento e comércio na Baixada",
    "Versão em uma página vs duas: o que funciona no RJ",
    "Palavras-chave reais vs palavras genéricas no PDF",
    "Como organizar conquistas com números honestos",
    "Currículo para quem voltou ao mercado depois de pausa",
    "Nome do arquivo, e-mail e telefone: detalhes que eliminam",
    "Currículo para vagas híbridas e presenciais no RJ",
    "Portfólio simples para áreas operacionais",
    "Como revisar o currículo em 15 minutos antes de enviar",
    "Diferença entre currículo para CLT e para estágio",
    "Currículo para logística e estoque na região metropolitana",
  ],
  "entrevista-de-emprego": [
    "Como se preparar para entrevista presencial no Centro do Rio",
    "Entrevista por vídeo: luz, áudio e plano B de internet",
    "Respostas STAR com exemplos do dia a dia fluminense",
    "O que perguntar no fim da entrevista sem soar genérico",
    "Como falar de pretensão salarial com pesquisa local",
    "Dinâmica em grupo: participar sem dominar a conversa",
    "Teste prático de Excel ou atendimento: como chegar calmo",
    "Atraso no trajeto: como avisar sem queimar a chance",
    "Entrevista em shopping ou loja: o que observar no posto",
    "Como explicar troca recente de emprego com clareza",
    "Perguntas sobre escala 6x1 e folga: o que esclarecer",
    "Entrevista com RH e com gestor: diferenças de tom",
    "Como ensaiar apresentação de 45 segundos",
    "Feedback negativo: o que fazer depois da entrevista",
    "Entrevista para jovem aprendiz: o que costuma pesar",
    "Como lidar com nervosismo sem discurso ensaiado demais",
    "Prova de inglês básico em processos do RJ",
    "Segunda entrevista: o que muda em relação à primeira",
    "Checklist do dia da entrevista na capital e Baixada",
  ],
  "primeiro-emprego": [
    "Primeiro emprego no RJ: documentos para ter prontos",
    "Sem experiência: como montar histórico honesto",
    "Vagas de auxiliar e apoio: onde começar na capital",
    "Estágio vs jovem aprendiz vs CLT de entrada",
    "Como usar indicação sem pagar intermediário",
    "Rotina de candidatura para quem ainda estuda",
    "Primeira entrevista: o que levar na pasta",
    "Erros comuns de quem busca a primeira carteira",
    "Cursos curtos que ajudam de verdade no primeiro emprego",
    "Como falar de disponibilidade de horário com clareza",
    "Voluntariado e projetos escolares no currículo",
    "Primeiro emprego na Baixada: deslocamento e expectativa",
    "Como registrar candidaturas para não reenviar o mesmo PDF",
    "Mensagem curta de WhatsApp para primeira oportunidade",
    "O que não aceitar em vaga de entrada duvidosa",
    "Como se preparar para admissão se a vaga sair",
    "Primeiro emprego em comércio e serviços no RJ",
    "Apoio familiar vs autonomia na busca do primeiro posto",
    "Meta semanal realista para quem nunca trabalhou",
  ],
  "mercado-de-trabalho-no-rj": [
    "Áreas com demanda recorrente no Rio de Janeiro",
    "Comércio, serviços e logística: panorama fluminense",
    "Turismo e hotelaria no RJ: sazonalidade e candidatura",
    "Mercado de petróleo e serviços em Macaé",
    "Indústria e metalurgia no Sul Fluminense",
    "Tecnologia e atendimento remoto a partir do RJ",
    "Baixada Fluminense: oportunidades e deslocamento",
    "Niterói e Região Metropolitana: diferenças de oferta",
    "Interior do RJ: Campos, Petrópolis e cabos de atração",
    "Como ler tendência sem cair em promessa milagrosa",
    "Vagas sazonais de verão no litoral fluminense",
    "Setor público vs privado: o que o candidato deve comparar",
    "Home office anunciado vs presencial na prática no RJ",
    "Salários e custo de deslocamento na Grande Rio",
    "Mercado para 50+: como se posicionar sem clichê",
    "Demanda por atendimento ao cliente no estado",
    "Construção civil e obras: cuidados na candidatura",
    "Educação e apoio escolar: vagas e requisitos típicos",
    "Como usar o Vagas RJ para mapear bairros e cidades",
  ],
  "seguranca-para-candidatos": [
    "Como identificar vaga falsa no WhatsApp e Telegram",
    "Taxa para ‘garantir’ emprego: sinais de golpe",
    "Entrevista em endereço estranho: quando recusar",
    "Pedido de selfie com documento: risco alto",
    "Pix, depósito e ‘kit de trabalho’ pagos pelo candidato",
    "Como conferir CNPJ e canal oficial da empresa",
    "Segurança para mulheres em processos presenciais",
    "Grupos de vagas: como filtrar sem cair em corrente",
    "Proposta urgente demais: checklist de verificação",
    "Dados bancários no primeiro contato: nunca",
    "Vaga ‘home office’ pedindo pagamento antecipado",
    "Como guardar prints e denunciar anúncio suspeito",
    "Intermediário que cobra para ‘indicar’: evite",
    "Segurança no deslocamento até a entrevista",
    "Links encurtados e páginas clonadas de empresas",
    "O que o Vagas RJ não faz: cobrar candidatura",
    "Como orientar familiar jovem sobre golpes de emprego",
    "Fake de RH pedindo senha do gov.br",
    "Checklist rápido antes de enviar documentos sensíveis",
  ],
  "direitos-e-cuidados-no-trabalho": [
    "CLT, estágio e jovem aprendiz: diferenças básicas",
    "Como ler o holerite sem se perder nos descontos",
    "Vale-transporte e deslocamento na Grande Rio",
    "Hora extra e banco de horas: o que perguntar",
    "Férias e abono: combinações que precisam de registro",
    "Período de experiência: direitos e deveres",
    "Assédio e canal de denúncia: postura segura",
    "EPI e segurança em funções operacionais",
    "Aviso prévio e pedido de demissão com organização",
    "Contrato PJ que na prática é CLT: sinais de alerta",
    "Intervalo e jornada: o que observar na escala",
    "Afastamento e atestado: comunicação objetiva",
    "Benefícios no anúncio vs o que sai no holerite",
    "Registro em CTPS digital: conferir após admissão",
    "Trabalho intermitente: quando faz sentido",
    "Estabilidade e estabilidade provisória em linguagem simples",
    "Como guardar documentos do vínculo com segurança",
    "Diferença entre prometido na entrevista e no contrato",
    "Onde buscar orientação oficial sem pagar consultoria duvidosa",
  ],
  "carreira-e-desenvolvimento": [
    "Como crescer de auxiliar para analista no RJ",
    "Plano de estudos barato alinhado à vaga-alvo",
    "Networking local sem pedido genérico no WhatsApp",
    "Marca pessoal simples: coerência de nome e tom",
    "Como pedir feedback depois de um processo",
    "Troca de área: transição com riscos controlados",
    "Certificações que o mercado fluminense realmente cita",
    "Organizar portfólio de tarefas operacionais",
    "Carreira em atendimento: evolução sem burnout",
    "Como manter consistência na busca sem se esgotar",
    "Mentoria informal: pedir ajuda com respeito",
    "Atualizar LinkedIn sem exagero para vagas locais",
    "Metas trimestrais de carreira para quem já está empregado",
    "Aprender no próprio posto: registrar ganhos",
    "Quando vale investir em curso pago",
    "Carreira híbrida: combinar estudo e escala",
    "Como documentar resultados para próxima promoção",
    "Recomeçar depois de demissão no RJ",
    "Equilíbrio entre emprego atual e busca discreta",
  ],
  "vagas-por-cidade": [
    "Como buscar vagas no Rio de Janeiro com foco por zona",
    "Vagas em Niterói: deslocamento e rotinas típicas",
    "Oportunidades em São Gonçalo e trajetos comuns",
    "Duque de Caxias: comércio, indústria e candidatura",
    "Nova Iguaçu: como filtrar anúncios da Baixada",
    "Petrópolis: turismo, serviços e temporada",
    "Volta Redonda e Sul Fluminense: perfil de vagas",
    "Campos dos Goytacazes: mercado do Norte Fluminense",
    "Cabo Frio: sazonalidade e vagas de verão",
    "Macaé: serviços, óleo e gás e requisitos frequentes",
    "Itaboraí: logística e obras — como se candidatar",
    "Belford Roxo: vagas locais e deslocamento para a capital",
    "Zona Sul do Rio: atendimento, hotelaria e comércio",
    "Zona Norte: oportunidades e tempo de trajeto",
    "Barra e Jacarepaguá: perfil de anúncios recorrentes",
    "Centro do Rio: escritórios e horários de pico",
    "Baixada Fluminense: mapa prático de busca",
    "Região dos Lagos: candidatura fora da alta temporada",
    "Como comparar custo de deslocamento entre cidades do RJ",
  ],
  "profissoes-e-areas": [
    "Como procurar vagas administrativas no RJ",
    "Atendente e caixa: o que os anúncios pedem de verdade",
    "Auxiliar de logística e estoque na Grande Rio",
    "Recepcionista: postura, sistemas e escala",
    "Vagas em clínicas e saúde administrativa",
    "Comércio varejista: ritmo de loja e metas",
    "Motorista e ajudante: documentos e cuidados",
    "Limpeza e conservação: EPI e contratos claros",
    "Call center e BPO: home office e presencial",
    "Cozinha e food service: higiene e pico de movimento",
    "TI de suporte N1: primeiro passo no RJ",
    "Vendas internas: meta, CRM e ética",
    "Educação infantil e apoio escolar: requisitos",
    "Segurança patrimonial: curso e postura",
    "Obra e construção: EPI e pagamento transparente",
    "Farmácia e drogaria: atendimento e plantão",
    "Hotelaria: turnos e temporada no Estado",
    "RH operacional e departamento pessoal de entrada",
    "Produção industrial no Sul Fluminense",
  ],
  "jovem-aprendiz-e-estagio": [
    "Como procurar vaga de Jovem Aprendiz no RJ",
    "Documentos e idade: o que conferir antes de candidatar",
    "Diferença entre aprendiz e estágio na prática",
    "Escola e empresa: como equilibrar horários",
    "Entrevista para aprendiz: o que costuma ser perguntado",
    "Estágio remunerado vs não remunerado: ler o edital",
    "Onde encontrar programas sérios no Estado",
    "Como montar currículo de aprendiz sem experiência CLT",
    "Direitos básicos do aprendiz em linguagem simples",
    "Cuidado com ‘curso obrigatório’ pago por fora",
    "Estágio em administração e atendimento no RJ",
    "Aprendiz em comércio: escala e aprendizagem real",
    "Como a família pode ajudar sem interferir no processo",
    "Primeiro dia como aprendiz: o que observar",
    "Renovação e término do contrato de aprendizagem",
    "Estágio em órgãos e empresas privadas: diferenças",
    "Transporte escolar e VT na rotina do aprendiz",
    "Como registrar o que aprendeu para o próximo emprego",
    "Golpes que usam nome de ‘programa aprendiz’",
  ],
  "dicas-para-candidatura": [
    "Rotina diária de candidatura sem maratona inútil",
    "Como priorizar 5 vagas bem lidas por dia",
    "E-mail de candidatura curto e legível",
    "WhatsApp profissional: tom, horário e arquivo",
    "Follow-up educado sem insistência excessiva",
    "Organizar planilha de envios e retornos",
    "Adaptar currículo em 10 minutos com método",
    "Quando desistir de uma vaga sem resposta",
    "Como comparar dois anúncios parecidos",
    "Checklist antes de clicar em ‘candidatar’",
    "Evitar candidatura em massa genérica",
    "Como usar filtros do portal a favor do tempo",
    "Preparar pasta digital de documentos nomeados",
    "Responder teste online sem perder por infraestrutura",
    "Combinar busca com emprego atual sem conflito ético",
    "Metas semanais e revisão do que não funcionou",
    "Como pedir indicação com texto pronto para encaminhar",
    "Candidatura para vaga afirmativa com segurança",
    "Última revisão: link, telefone e PDF abrindo no celular",
  ],
};

const LOCAL_ANGLES = [
  "No Rio, tempo de deslocamento muda a conta da vaga tanto quanto o salário anunciado.",
  "Na Baixada, anúncios bons somem rápido — candidatura objetiva no mesmo dia importa.",
  "Em Niterói e São Gonçalo, o trajeto pela ponte ou ônibus define se a escala fecha a vida.",
  "No Centro, entrevistas em horário de pico pedem margem de atraso zero e rota alternativa.",
  "Em Macaé e Campos, requisitos técnicos aparecem cedo; inventar experiência queima reputação.",
  "No litoral, temporada aumenta vaga e também anúncio duvidoso — filtre canal oficial.",
  "Em Volta Redonda e Sul Fluminense, processos industriais valorizam pontualidade e EPI.",
  "Zona Sul e Barra misturam hotelaria e comércio: postura e disponibilidade pesam juntos.",
  "Na Grande Rio, VT e integração de transporte precisam estar claros antes do aceite.",
  "Grupos de WhatsApp locais misturam vaga real e golpe — confirme CNPJ e endereço.",
  "Recrutadores fluminenses costumam preferir PDF leve e mensagem curta a currículo decorado.",
  "Chuva e trânsito no RJ não são desculpa eterna, mas avisam com educação se o atraso for real.",
  "Empresas sérias no Estado não cobram taxa para candidatar nem para ‘liberar vaga’.",
  "Indicação ajuda, mas no RJ a conversa ainda passa por currículo e entrevista coerentes.",
  "Escala 6x1 é comum em serviços: confirme folga e feriado antes de aceitar no impulso.",
];

const TIP_POOLS = [
  "Leia o anúncio até o fim e marque cargo, cidade, escala e canal oficial antes de enviar qualquer arquivo.",
  "Adapte só o que é verdadeiro: ecoar a linguagem da vaga não é mentir sobre o histórico.",
  "Salve o PDF com seu nome e o cargo-alvo; evite ‘curriculo final final.pdf’.",
  "Teste abrir o arquivo no celular — muitos recrutadores leem no ônibus.",
  "Anote data, empresa e resposta em uma planilha simples para não reenviar o mesmo material cego.",
  "Se o canal for WhatsApp, mande mensagem curta em horário comercial com PDF leve.",
  "Confirme endereço da entrevista e avise alguém de confiança sobre horário e local.",
  "Pergunte sobre VT, vale-refeição e escala com objetividade — não deixe para ‘depois’.",
  "Desconfie de pressa artificial, taxa antecipada ou pedido de dado bancário no primeiro contato.",
  "Leve RG, CPF e comprovante organizados se a etapa for presencial de documentos.",
  "Ensaie uma apresentação de menos de um minuto: quem você é, o que já fez, o que busca.",
  "Compare o líquido da proposta com o tempo de deslocamento na sua região do RJ.",
  "Guarde print do anúncio com data caso a descrição mude depois.",
  "Peça indicação só a quem viu você trabalhar de verdade.",
  "Revise e-mail e telefone duas vezes — erro simples elimina candidato bom.",
  "Na entrevista, prefira exemplos concretos a adjetivos vazios.",
  "Se houver teste online, carregue o aparelho e tenha plano B de dados.",
  "Não publique reclamação pública de processo ainda em andamento.",
  "Se a vaga for aprendiz ou estágio, confirme idade, escola e carga horária compatíveis.",
  "Depois de enviar, faça um follow-up educado uma vez — sem cobrança diária.",
];

function slugify(text) {
  return String(text)
    .normalize("NFD")
    .replace(/[\u0300-\u036f]/g, "")
    .toLowerCase()
    .replace(/[^a-z0-9]+/g, "-")
    .replace(/^-+|-+$/g, "")
    .slice(0, 72);
}

function pick(arr, index) {
  return arr[Math.abs(index) % arr.length];
}

function buildTips(index, city, title) {
  const tips = [];
  for (let i = 0; i < 6; i += 1) {
    const base = pick(TIP_POOLS, index * 7 + i * 3);
    tips.push(
      `${base} Aplique isso em “${title}” com referência a ${city}, registrando o que funcionou neste envio específico.`,
    );
  }
  return tips;
}

function buildLead(title, city, index) {
  const hooks = [
    `Você abriu dez anúncios parecidos e ainda não sabe por onde começar. Este guia sobre “${title}” organiza o próximo passo com foco em ${city} e no restante do Rio de Janeiro.`,
    `A pressa de candidatar-se em massa costuma render silêncio. Em “${title}”, o caminho é outro: critério, adaptação honesta e checagem de segurança antes do envio.`,
    `Recrutadores no RJ leem rápido e descartam o que parece genérico. Este texto trata de “${title}” com exemplos úteis para quem busca vaga em ${city} ou região.`,
    `Entre metrô, BRT, trem e trânsito, o candidato fluminense já gasta energia antes da entrevista. “${title}” ajuda a gastar o restante com método, não com improviso.`,
    `Golpe e vaga séria circulam no mesmo grupo de WhatsApp. Ao falar de “${title}”, separamos o que é prática de candidatura do que é risco — com olhar local para ${city}.`,
  ];
  return pick(hooks, index);
}

/** @type {import('./rj-local-editorial-catalog.mjs').CatalogItem[]} */
const raw = [];
let n = 0;
for (const categorySlug of CATEGORIES) {
  const topics = TOPIC_BANK[categorySlug];
  for (let i = 0; i < topics.length; i += 1) {
    if (raw.length >= 205) break;
    const title = topics[i];
    const city = pick(CITIES, n + i);
    const key = `${String(n + 1).padStart(3, "0")}-${slugify(title).slice(0, 48)}`;
    raw.push({
      key,
      title,
      categorySlug,
      keyword: slugify(`${title} ${city} rj`),
      lead: buildLead(title, city, n),
      tips: buildTips(n, city, title),
      localAngle: pick(LOCAL_ANGLES, n),
      coverHue: (n * 17) % 360,
      section: categorySlug,
      cityFocus: city,
    });
    n += 1;
  }
}

// Completar até 205 com variações cidade × tema residual
const FILLERS = [
  ["Checklist de candidatura segura", "seguranca-para-candidatos"],
  ["Como revisar benefícios antes de aceitar", "direitos-e-cuidados-no-trabalho"],
  ["Organizar a semana de busca de emprego", "dicas-para-candidatura"],
  ["Preparar documentos de admissão", "primeiro-emprego"],
  ["Melhorar retorno das candidaturas", "carreira-e-desenvolvimento"],
  ["Ler anúncio de vaga com atenção", "dicas-para-candidatura"],
];

let fill = 0;
while (raw.length < 205) {
  const city = pick(CITIES, raw.length);
  const [stem, cat] = FILLERS[fill % FILLERS.length];
  fill += 1;
  const title = `${stem} em ${city}`;
  const key = `${String(raw.length + 1).padStart(3, "0")}-${slugify(title).slice(0, 48)}`;
  const index = raw.length;
  raw.push({
    key,
    title,
    categorySlug: cat,
    keyword: slugify(`${title} rj`),
    lead: buildLead(title, city, index),
    tips: buildTips(index, city, title),
    localAngle: pick(LOCAL_ANGLES, index),
    coverHue: (index * 17) % 360,
    section: cat,
    cityFocus: city,
  });
}

export const catalog = raw.slice(0, 205);

export function slugFor(item, index) {
  const i = typeof index === "number" ? index : catalog.indexOf(item);
  const n = String((i >= 0 ? i : 0) + 1).padStart(3, "0");
  return `${SLUG_BASE}-${n}-${slugify(item.title).slice(0, 56)}`;
}

export function catalogIndexFromSlug(slug) {
  const m = String(slug).match(new RegExp(`^${SLUG_BASE}-(\\d{3})-`));
  if (!m) return null;
  const index = Number(m[1]) - 1;
  return Number.isInteger(index) && index >= 0 && index < catalog.length ? index : null;
}
