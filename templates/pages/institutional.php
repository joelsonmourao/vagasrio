<?php
$contactEmail = (string) config('site.contact_email');
$contentMap = [
    'sobre' => [
        'title' => 'Sobre o Vagas RJ',
        'html' => '<p>O <strong>Vagas RJ</strong> é um portal regional de divulgação de oportunidades de emprego focado exclusivamente no estado do Rio de Janeiro. Reunimos vagas publicadas por empresas e fontes externas para facilitar a busca por cargo, cidade, empresa e categoria.</p>'
            . '<h2>Nossa missão</h2><p>Conectar candidatos a oportunidades reais com navegação clara, páginas rápidas e informações transparentes. Não participamos de processos seletivos, não garantimos contratação e não cobramos taxa de candidatura.</p>'
            . '<h2>Como usamos o conteúdo</h2><p>Cada vaga exibe descrição própria fornecida pela fonte original. Mantemos blog com orientações sobre currículo, entrevistas, mercado de trabalho no RJ e segurança para candidatos — sempre com conteúdo original e útil.</p>'
            . '<h2>Publicidade</h2><p>Podemos exibir anúncios do Google AdSense em páginas públicas, respeitando espaços reservados para evitar impacto na experiência. Anúncios não aparecem no painel administrativo nem em páginas de erro.</p>',
    ],
    'contato' => [
        'title' => 'Contato',
        'html' => '<p>Entre em contato para dúvidas sobre o portal, sugestões de melhoria, correção de informações ou questões sobre privacidade e publicidade.</p>'
            . '<h2>E-mail</h2><p><a href="mailto:' . e($contactEmail) . '">' . e($contactEmail) . '</a></p>'
            . '<h2>Formulário</h2><p>Use o formulário abaixo para montar sua mensagem. Você será direcionado ao seu cliente de e-mail.</p>',
        'form' => true,
    ],
    'politica-de-privacidade' => [
        'title' => 'Política de Privacidade',
        'html' => '<p>Esta política descreve como o Vagas RJ trata dados pessoais e informações de navegação.</p>'
            . '<h2>Dados que coletamos</h2><p>Coletamos dados mínimos de navegação (como páginas visitadas e tipo de dispositivo) por meio de cookies e ferramentas de análise, quando ativadas. Dados enviados em candidaturas são tratados diretamente pela empresa contratante, fora deste portal.</p>'
            . '<h2>Cookies e publicidade</h2><p>Utilizamos cookies essenciais para funcionamento do site e, com consentimento quando aplicável, cookies de análise e publicidade — incluindo parceiros como Google AdSense. Consulte a <a href="' . e(url_path('/politica-de-cookies')) . '">Política de Cookies</a>.</p>'
            . '<h2>Seus direitos</h2><p>Você pode solicitar esclarecimentos sobre tratamento de dados pelo e-mail <a href="mailto:' . e($contactEmail) . '">' . e($contactEmail) . '</a>. Podemos atualizar esta política; a data da versão vigente será indicada nesta página.</p>',
    ],
    'politica-de-cookies' => [
        'title' => 'Política de Cookies',
        'html' => '<p>Cookies são pequenos arquivos armazenados no seu navegador. Esta página explica como o Vagas RJ os utiliza.</p>'
            . '<h2>Cookies essenciais</h2><p>Necessários para segurança, sessão administrativa e funcionamento básico. Não exigem consentimento para operação mínima do site público.</p>'
            . '<h2>Cookies de análise</h2><p>Quando habilitados, ajudam a entender uso agregado do portal (páginas mais acessadas, origem de tráfego) para melhorar conteúdo e performance.</p>'
            . '<h2>Cookies de publicidade</h2><p>Parceiros como Google AdSense podem usar cookies para exibir anúncios relevantes e medir desempenho. Você pode gerenciar preferências no banner de cookies ou nas configurações do navegador.</p>'
            . '<h2>Como desativar</h2><p>É possível bloquear ou apagar cookies nas configurações do navegador. Algumas funções do site podem ficar limitadas.</p>',
    ],
    'termos-de-uso' => [
        'title' => 'Termos de Uso',
        'html' => '<p>Ao utilizar o Vagas RJ, você concorda com estes termos.</p>'
            . '<h2>Natureza do serviço</h2><p>O portal apenas <strong>divulga oportunidades</strong> encontradas ou cadastradas por terceiros. Não somos empregador, agência de recrutamento ou parte do processo seletivo.</p>'
            . '<h2>Sem garantia de contratação</h2><p>Não garantimos vaga, entrevista ou contratação. Informações podem ser alteradas pela empresa contratante a qualquer momento.</p>'
            . '<h2>Responsabilidade do candidato</h2><p>Confirme dados no site oficial da empresa ou no link de candidatura antes de enviar documentos. Desconfie de cobranças para participar de seleção.</p>'
            . '<h2>Conteúdo de terceiros</h2><p>Descrições de vagas e links externos são de responsabilidade das fontes originais. Removemos conteúdo reportado como inadequado quando possível.</p>',
    ],
    'aviso-legal' => [
        'title' => 'Aviso Legal',
        'html' => '<p>Informações legais sobre operação do Vagas RJ.</p>'
            . '<h2>Divulgação de vagas</h2><p>Publicamos oportunidades para facilitar o encontro entre candidatos e empresas. Não representamos marcas listadas salvo indicação expressa.</p>'
            . '<h2>Taxa de candidatura</h2><p><strong>O Vagas RJ não cobra taxa</strong> para visualizar vagas, enviar currículo ou participar de processos divulgados. Pedidos de pagamento em nome do portal são fraudulentos.</p>'
            . '<h2>Limitação</h2><p>Empregamos esforços razoáveis para manter informações atualizadas, mas não nos responsabilizamos por alterações feitas pelas empresas após publicação.</p>'
            . '<h2>Contato legal</h2><p>Questões jurídicas: <a href="mailto:' . e($contactEmail) . '">' . e($contactEmail) . '</a>.</p>',
    ],
    'seguranca-para-candidatos' => [
        'title' => 'Segurança para candidatos',
        'html' => '<p>Orientações para evitar golpes ao buscar emprego no Rio de Janeiro.</p>'
            . '<h2>Sinais de alerta</h2><ul><li>Pedido de pagamento para candidatura ou treinamento</li><li>Promessa de contratação imediata sem entrevista</li><li>Links que não levam ao site oficial da empresa</li><li>Solicitação de senha bancária ou PIX antecipado</li></ul>'
            . '<h2>Antes de enviar documentos</h2><p>Confirme CNPJ, site e redes oficiais da empresa. Prefira canais de candidatura indicados na descrição da vaga.</p>'
            . '<h2>Grupos e mensagens</h2><p>Desconfie de vagas genéricas em grupos de WhatsApp ou Telegram. Cruce informações com portais confiáveis e descrições completas.</p>'
            . '<h2>Denúncia</h2><p>Se identificar fraude, pare o contato e reporte ao canal oficial da empresa e, se necessário, às autoridades competentes. Você também pode avisar o Vagas RJ em <a href="mailto:' . e($contactEmail) . '">' . e($contactEmail) . '</a>.</p>',
    ],
    'mapa-do-site' => [
        'title' => 'Mapa do site',
        'html' => '',
        'sitemap' => true,
    ],
];
$item = $contentMap[$institutionalType] ?? $contentMap['sobre'];
?>
<section class="page-hero">
    <div class="page-hero-inner">
        <h1><?= e($item['title']) ?></h1>
    </div>
</section>
<article class="panel prose institutional-prose">
    <?= $item['html'] ?>
    <?php if (!empty($item['form'])): ?>
        <form class="contact-form" method="get" action="mailto:<?= e($contactEmail) ?>">
            <label>Nome<input type="text" name="name" required autocomplete="name"></label>
            <label>E-mail<input type="email" name="email" required autocomplete="email"></label>
            <label>Assunto<input type="text" name="subject" required value="Contato Vagas RJ"></label>
            <label>Mensagem<textarea name="body" rows="6" required placeholder="Descreva sua dúvida ou solicitação"></textarea></label>
            <button class="btn btn-accent" type="submit">Enviar por e-mail</button>
        </form>
    <?php endif; ?>
    <?php if (!empty($item['sitemap'])): ?>
        <p>Navegue por todas as áreas públicas do Vagas RJ. Para mecanismos de busca, consulte também <a href="<?= e(base_url('/sitemap.xml')) ?>">sitemap.xml</a>.</p>
        <div class="sitemap-section">
            <h2>Páginas principais</h2>
            <ul>
                <li><a href="<?= e(url_path('/')) ?>">Home</a></li>
                <li><a href="<?= e(url_path('/vagas')) ?>">Vagas</a></li>
                <li><a href="<?= e(url_path('/cidades')) ?>">Cidades</a></li>
                <li><a href="<?= e(url_path('/empresas')) ?>">Empresas</a></li>
                <li><a href="<?= e(url_path('/categorias')) ?>">Categorias</a></li>
                <li><a href="<?= e(url_path('/blog')) ?>">Blog</a></li>
            </ul>
        </div>
        <div class="sitemap-section">
            <h2>Institucional</h2>
            <ul>
                <li><a href="<?= e(url_path('/sobre')) ?>">Sobre</a></li>
                <li><a href="<?= e(url_path('/contato')) ?>">Contato</a></li>
                <li><a href="<?= e(url_path('/politica-de-privacidade')) ?>">Política de Privacidade</a></li>
                <li><a href="<?= e(url_path('/politica-de-cookies')) ?>">Política de Cookies</a></li>
                <li><a href="<?= e(url_path('/termos-de-uso')) ?>">Termos de Uso</a></li>
                <li><a href="<?= e(url_path('/aviso-legal')) ?>">Aviso Legal</a></li>
                <li><a href="<?= e(url_path('/seguranca-para-candidatos')) ?>">Segurança para candidatos</a></li>
            </ul>
        </div>
        <div class="sitemap-columns">
            <div class="sitemap-section">
                <h2>Cidades do RJ</h2>
                <ul>
                    <?php foreach ($mapCities ?? [] as $city): ?>
                        <li><a href="<?= e(city_public_path($city['slug'])) ?>"><?= e($city['name']) ?></a></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <div class="sitemap-section">
                <h2>Categorias de vagas</h2>
                <ul>
                    <?php foreach ($mapCategories ?? [] as $category): ?>
                        <li><a href="<?= e(category_public_path($category['slug'])) ?>"><?= e($category['name']) ?></a></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <div class="sitemap-section">
                <h2>Empresas</h2>
                <ul>
                    <?php foreach ($mapCompanies ?? [] as $company): ?>
                        <li><a href="<?= e(company_public_path($company['slug'])) ?>"><?= e($company['name']) ?></a></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <div class="sitemap-section">
                <h2>Categorias do blog</h2>
                <ul>
                    <?php foreach ($mapBlogCategories ?? [] as $cat): ?>
                        <li><a href="<?= e(blog_category_public_path($cat['slug'])) ?>"><?= e($cat['name']) ?></a></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
        <?php if (!empty($mapRecentArticles)): ?>
            <div class="sitemap-section">
                <h2>Artigos recentes do blog</h2>
                <ul>
                    <?php foreach ($mapRecentArticles as $article): ?>
                        <li><a href="<?= e(url_path('/blog/' . $article['slug'])) ?>"><?= e($article['title']) ?></a></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</article>
