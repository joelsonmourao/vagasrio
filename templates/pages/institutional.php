<?php
$contentMap = [
    'sobre' => [
        'title' => 'Sobre o Vagas RJ',
        'html' => '<p>O Vagas RJ é um portal focado exclusivamente em oportunidades de emprego no estado do Rio de Janeiro.</p><p>Nosso objetivo é facilitar o encontro entre candidatos e vagas reais, com navegação rápida, transparente e segura.</p>',
    ],
    'contato' => [
        'title' => 'Contato',
        'html' => '<p>Para suporte, sugestões ou correção de dados, envie mensagem para contato@exemplo.com.</p><p>Retornamos em horário comercial.</p>',
    ],
    'politica-de-privacidade' => [
        'title' => 'Política de Privacidade',
        'html' => '<p>O Vagas RJ coleta dados mínimos de navegação para melhorar performance, segurança e experiência.</p><p>Podemos utilizar serviços de terceiros para analytics e publicidade, incluindo Google AdSense, com uso de cookies.</p><p>Os dados de candidatura são processados fora do portal, diretamente no site de destino informado na vaga.</p>',
    ],
    'politica-de-cookies' => [
        'title' => 'Política de Cookies',
        'html' => '<p>Utilizamos cookies essenciais para funcionamento do site e cookies opcionais para medir uso e personalizar publicidade.</p><p>Ao aceitar cookies, você permite esse uso conforme a legislação aplicável.</p><p>Você pode limpar ou bloquear cookies no navegador a qualquer momento.</p>',
    ],
    'termos-de-uso' => [
        'title' => 'Termos de Uso',
        'html' => '<p>O Vagas RJ apenas divulga vagas encontradas ou cadastradas por fontes externas e empresas.</p><p>Não cobramos taxa de candidatura, não garantimos contratação e não participamos da seleção.</p><p>O candidato deve validar informações no site oficial da empresa ou no link de candidatura.</p>',
    ],
];
$item = $contentMap[$institutionalType] ?? $contentMap['sobre'];
?>
<section class="page-hero">
    <div class="page-hero-inner">
        <h1><?= $item['title'] ?></h1>
    </div>
</section>
<article class="panel prose"><?= $item['html'] ?></article>
