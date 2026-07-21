/**
 * FAQs no padrão dos grandes portais (Vagas.com, Catho, InfoJobs)
 * e das perguntas que o Google costuma mostrar para “vagas rio / empregos rio / vagas rj”.
 * Visíveis na página + FAQPage schema.
 */

export type JobsFaqItem = {
  question: string;
  answer: string;
};

const brandFallback = 'Vagas RJ RIO';

export function getRjJobsFaqs(opts: {
  brand?: string;
  jobCount?: number;
  cities?: string[];
}): JobsFaqItem[] {
  const brand = opts.brand?.trim() || brandFallback;
  const count = typeof opts.jobCount === 'number' ? opts.jobCount : 0;
  const countLabel = count > 0 ? `${count} vaga${count === 1 ? '' : 's'}` : 'vagas';
  const cityList =
    (opts.cities && opts.cities.length > 0
      ? opts.cities.slice(0, 8).join(', ')
      : 'Rio de Janeiro, Niterói, São Gonçalo, Duque de Caxias, Nova Iguaçu, Petrópolis') +
    ' e outras cidades do estado';

  return [
    {
      question: 'Como encontrar vagas de emprego no Rio de Janeiro?',
      answer: `No ${brand} você busca vagas de emprego no Rio de Janeiro (RJ) por cargo, cidade, empresa e área. Use a busca na home ou em /vagas, filtre o local de trabalho e abra o anúncio para seguir ao canal oficial da empresa. Hoje há ${countLabel} pública${count === 1 ? '' : 's'} no portal.`,
    },
    {
      question: 'O que são vagas RJ, empregos Rio e vagas rio de janeiro?',
      answer:
        'São a mesma busca: oportunidades de trabalho no estado do Rio de Janeiro. As pessoas pesquisam “vagas rio”, “empregos rio”, “rio vagas”, “vagas rj” e “vagas de emprego no Rio de Janeiro”. O portal reúne anúncios regionais para facilitar essa pesquisa.',
    },
    {
      question: 'As vagas de emprego no RJ são gratuitas para candidatos?',
      answer: `Sim. Candidatar-se pelas vagas divulgadas no ${brand} é gratuito para o candidato. Desconfie de qualquer pedido de pagamento, taxa, “kit” ou depósito para liberar vaga ou entrevista.`,
    },
    {
      question: 'Como me candidatar a uma vaga de emprego no Rio?',
      answer:
        'Abra a vaga, leia a descrição e os requisitos e use o botão de candidatura. Você será direcionado ao site, e-mail ou outro canal informado pela empresa contratante. O portal apenas divulga a oportunidade e não participa do processo seletivo.',
    },
    {
      question: 'Quais cidades do RJ têm vagas de emprego?',
      answer: `Há oportunidades em ${cityList}. Você também pode navegar em /cidades ou filtrar por cidade na busca de vagas.`,
    },
    {
      question: 'Preciso pagar alguma taxa para ver empregos RJ?',
      answer: `Não. Consultar e candidatar-se às vagas no ${brand} é grátis. Empresa séria no Rio de Janeiro não cobra do candidato para participar do processo seletivo.`,
    },
    {
      question: 'Como filtrar vagas por cargo, empresa ou área no Rio de Janeiro?',
      answer:
        'Na página de vagas, use os campos de busca e filtros: cargo/palavra-chave, cidade (local de trabalho), empresa e categoria/área. Depois clique em buscar. Dá para limpar os filtros e recomeçar a qualquer momento.',
    },
    {
      question: 'As vagas são atualizadas com frequência?',
      answer: `Sim. O ${brand} publica e atualiza anúncios de emprego no RJ com regularidade. A listagem mostra as oportunidades públicas mais recentes; confira sempre data de publicação e validade no anúncio.`,
    },
    {
      question: 'Como identificar vaga falsa no Rio de Janeiro?',
      answer:
        'Sinais de alerta: cobrança para candidatar, pedido de dados bancários ou senha do gov.br no primeiro contato, entrevista em endereço estranho sem verificação e promessa de salário alto sem requisitos. Confira CNPJ e canal oficial da empresa e guarde prints. Veja também as dicas de segurança no blog.',
    },
    {
      question: 'O portal é o empregador das vagas?',
      answer: `Não. O ${brand} é um portal de divulgação de vagas de emprego no Rio de Janeiro. Quem contrata é a empresa anunciante. Sempre valide informações no link ou contato oficial antes de enviar documentos sensíveis.`,
    },
  ];
}
