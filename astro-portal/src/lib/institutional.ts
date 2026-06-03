/** Conteúdo das páginas institucionais (SEO, AdSense, confiança). */
export const institutionalPages: Record<string, { title: string; description: string; html: string }> = {
  sobre: {
    title: 'Sobre o Vagas RJ',
    description:
      'Conheça o Vagas RJ: portal regional de divulgação de vagas de emprego no Rio de Janeiro (RJ), com busca por cidade, empresa e categoria.',
    html: `
<p>O <strong>Vagas RJ</strong> é um portal regional independente de <strong>divulgação de oportunidades de emprego</strong>, com foco exclusivo no estado do Rio de Janeiro (RJ). Não somos empresa contratante: reunimos anúncios publicados por terceiros para facilitar a busca de quem procura trabalho na região.</p>
<h2>O que fazemos</h2>
<ul>
  <li>Organizamos vagas por cidade, empresa, categoria e palavra-chave.</li>
  <li>Publicamos artigos no blog com orientações sobre currículo, entrevistas e segurança na candidatura.</li>
  <li>Mantemos páginas claras sobre privacidade, cookies, termos de uso e contato.</li>
</ul>
<h2>O que não fazemos</h2>
<ul>
  <li>Não garantimos contratação nem participamos de processos seletivos.</li>
  <li>Não cobramos do candidato para se candidatar a uma vaga divulgada aqui.</li>
  <li>Não substituímos o site oficial da empresa anunciante — sempre confira a fonte antes de enviar dados.</li>
</ul>
<h2>Transparência</h2>
<p>O portal pode exibir publicidade (Google AdSense) e ferramentas de medição de audiência quando configuradas, sempre em conformidade com nossa <a href="/politica-de-privacidade">Política de Privacidade</a> e <a href="/politica-de-cookies">Política de Cookies</a>.</p>
<p>Dúvidas ou sugestões: <a href="/contato">página de contato</a>.</p>`,
  },
  contato: {
    title: 'Contato',
    description:
      'Entre em contato com o Vagas RJ para dúvidas, correções de vagas, sugestões e questões sobre privacidade ou publicidade.',
    html: `
<p>Use os canais abaixo para falar conosco. Respondemos solicitações relacionadas ao portal, conteúdo publicado e políticas do site.</p>
<h2>E-mail</h2>
<p>Envie sua mensagem para <a href="mailto:contato@vagasrj.rio.br">contato@vagasrj.rio.br</a>.</p>
<h2>Quando entrar em contato</h2>
<ul>
  <li><strong>Dúvidas gerais</strong> sobre navegação, filtros ou cadastro de vagas.</li>
  <li><strong>Correções</strong> se uma vaga estiver desatualizada, duplicada ou com link incorreto.</li>
  <li><strong>Privacidade e cookies</strong> — pedidos de esclarecimento conforme a LGPD.</li>
  <li><strong>Publicidade</strong> — questões sobre anúncios exibidos via Google AdSense, quando aplicável.</li>
</ul>
<p>Não atendemos por este canal processos seletivos de empresas anunciantes; para candidatura, utilize o link oficial indicado em cada vaga.</p>
<p><a href="/vagas">Ver vagas abertas</a> · <a href="/sobre">Sobre o portal</a></p>`,
  },
  'politica-de-privacidade': {
    title: 'Política de Privacidade',
    description:
      'Política de privacidade do Vagas RJ: dados coletados, cookies, Google Analytics, Google AdSense e seus direitos.',
    html: `
<p>Esta Política de Privacidade descreve como o <strong>Vagas RJ</strong> trata informações quando você visita nosso site. Ao continuar navegando, você declara ter lido este documento.</p>
<h2>Quem somos</h2>
<p>O Vagas RJ é um portal de divulgação de vagas de emprego no Rio de Janeiro (RJ). Para contato: <a href="mailto:contato@vagasrj.rio.br">contato@vagasrj.rio.br</a> ou <a href="/contato">/contato</a>.</p>
<h2>Dados que podemos tratar</h2>
<ul>
  <li><strong>Dados de navegação</strong> (endereço IP, tipo de navegador, páginas visitadas, data/hora), via cookies e logs do servidor.</li>
  <li><strong>Dados enviados por você</strong> quando utilizar formulários de contato ou áreas administrativas (não aplicável ao visitante comum).</li>
  <li><strong>Dados de medição</strong> quando o Google Analytics (GA4) estiver ativo no site.</li>
</ul>
<h2>Google Analytics</h2>
<p>Podemos utilizar o Google Analytics para entender o uso do site (páginas mais visitadas, origem do tráfego). O Google pode processar dados conforme sua própria política. Você pode instalar a extensão de opt-out do Google ou gerenciar cookies no navegador.</p>
<h2>Google AdSense e publicidade</h2>
<p>Podemos exibir anúncios do <strong>Google AdSense</strong>. Nesse caso, o Google e seus parceiros podem usar cookies para personalizar ou medir anúncios, conforme suas configurações em <a href="https://adssettings.google.com" rel="noopener noreferrer" target="_blank">adssettings.google.com</a>. O arquivo <a href="/ads.txt">ads.txt</a> do domínio identifica o editor autorizado quando configurado.</p>
<h2>Cookies</h2>
<p>Utilizamos cookies essenciais e, com seu consentimento quando aplicável, cookies de análise e publicidade. Detalhes em <a href="/politica-de-cookies">Política de Cookies</a>.</p>
<h2>Base legal e finalidades (LGPD)</h2>
<p>Tratamos dados com base em legítimo interesse (segurança e melhoria do serviço), execução de medidas pré-contratuais quando você nos contata, e consentimento quando exigido para cookies não essenciais.</p>
<h2>Seus direitos</h2>
<p>Você pode solicitar acesso, correção ou exclusão de dados pessoais que tratarmos diretamente, enviando e-mail para contato@vagasrj.rio.br. Responderemos em prazo razoável.</p>
<h2>Retenção e segurança</h2>
<p>Mantemos logs e configurações pelo tempo necessário à operação do portal. Adotamos medidas técnicas razoáveis (HTTPS em produção, acesso restrito ao painel administrativo).</p>
<h2>Alterações</h2>
<p>Esta política pode ser atualizada. A data da versão vigente será indicada no rodapé ou nesta página quando alterada.</p>`,
  },
  'politica-de-cookies': {
    title: 'Política de Cookies',
    description:
      'Saiba quais cookies o Vagas RJ utiliza: essenciais, Google Analytics, Google AdSense e como gerenciar preferências.',
    html: `
<p>Esta página explica o uso de <strong>cookies</strong> e tecnologias semelhantes no site Vagas RJ.</p>
<h2>O que são cookies</h2>
<p>Cookies são pequenos arquivos armazenados no seu navegador que permitem lembrar preferências ou medir o uso do site.</p>
<h2>Tipos de cookies que podemos usar</h2>
<ul>
  <li><strong>Essenciais</strong> — necessários ao funcionamento (ex.: sessão do painel administrativo, preferência de consentimento de cookies).</li>
  <li><strong>Análise</strong> — Google Analytics (GA4), quando ativo, para estatísticas agregadas de visitas.</li>
  <li><strong>Publicidade</strong> — Google AdSense e parceiros, quando ativos, para exibir e medir anúncios.</li>
</ul>
<h2>Consentimento</h2>
<p>Ao clicar em &quot;Aceitar&quot; no aviso de cookies do site, você concorda com cookies não essenciais conforme esta política. Você pode recusar ou apagar cookies nas configurações do navegador a qualquer momento; parte do site pode deixar de funcionar corretamente.</p>
<h2>Como gerenciar cookies</h2>
<ul>
  <li>Chrome, Firefox, Safari e Edge: menu de privacidade / cookies do navegador.</li>
  <li>Desativar anúncios personalizados do Google: <a href="https://adssettings.google.com" rel="noopener noreferrer" target="_blank">adssettings.google.com</a>.</li>
</ul>
<h2>Cookies de terceiros</h2>
<p>Google (Analytics, AdSense) pode definir cookies próprios. Consulte as políticas do Google para mais informações.</p>
<p>Questões: <a href="/contato">contato</a> · <a href="/politica-de-privacidade">privacidade</a>.</p>`,
  },
  'termos-de-uso': {
    title: 'Termos de Uso',
    description:
      'Termos de uso do Vagas RJ: divulgação de vagas, limitações de responsabilidade, links externos e regras para candidatos.',
    html: `
<p>Ao acessar o <strong>Vagas RJ</strong>, você concorda com estes Termos de Uso. Se não concordar, não utilize o site.</p>
<h2>Natureza do serviço</h2>
<p>O Vagas RJ <strong>apenas divulga oportunidades</strong> de emprego no estado do Rio de Janeiro. Não somos empregador, agência de recrutamento nem garantimos contratação.</p>
<h2>Candidatura</h2>
<ul>
  <li>A candidatura é feita no site ou canal indicado pela <strong>empresa anunciante</strong>, não neste portal.</li>
  <li><strong>Não cobramos</strong> taxas, depósitos ou pagamentos para candidatura.</li>
  <li>Desconfie de pedidos de dinheiro, senhas bancárias ou documentos em canais não oficiais.</li>
</ul>
<h2>Responsabilidade das empresas</h2>
<p>Cada empresa é responsável pelo conteúdo, veracidade e legalidade das vagas que publica ou autoriza a publicar. O Vagas RJ pode remover anúncios suspeitos, fraudulentos ou que violem estes termos.</p>
<h2>Conteúdo do blog</h2>
<p>Artigos têm caráter informativo e não constituem aconselhamento jurídico ou garantia de emprego.</p>
<h2>Links externos</h2>
<p>Links para sites de terceiros (empresas, candidatura) não implicam endosso. Acesse por sua conta e risco.</p>
<h2>Propriedade intelectual</h2>
<p>Textos, marca e layout do portal pertencem ao Vagas RJ ou licenciadores. É proibida cópia automatizada em massa sem autorização.</p>
<h2>Limitação de responsabilidade</h2>
<p>O site é oferecido &quot;como está&quot;. Não nos responsabilizamos por danos indiretos decorrentes do uso das informações publicadas ou de sites de terceiros.</p>
<h2>Alterações</h2>
<p>Podemos alterar estes termos a qualquer momento. O uso continuado após alterações constitui aceite.</p>
<p><a href="/politica-de-privacidade">Privacidade</a> · <a href="/contato">Contato</a></p>`,
  },
};
