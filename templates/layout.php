<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($title ?? config('site.name')) ?></title>
    <meta name="description" content="<?= e($description ?? 'Vagas de emprego no Rio de Janeiro (RJ) por cidade, empresa e categoria.') ?>">
    <meta name="robots" content="<?= e($robots ?? 'index,follow') ?>">
    <link rel="canonical" href="<?= e($canonical ?? base_url(current_path())) ?>">
    <meta property="og:type" content="website">
    <meta property="og:title" content="<?= e($title ?? config('site.name')) ?>">
    <meta property="og:description" content="<?= e($description ?? '') ?>">
    <meta property="og:url" content="<?= e($canonical ?? base_url(current_path())) ?>">
    <meta property="og:site_name" content="<?= e(config('site.name')) ?>">
    <meta name="twitter:card" content="summary_large_image">
    <link rel="stylesheet" href="/assets/css/app.css">
</head>
<body>
<?php require ROOT_PATH . '/templates/partials/header.php'; ?>
<main class="container">
    <?= $content ?>
</main>
<?php require ROOT_PATH . '/templates/partials/footer.php'; ?>
<div id="cookie-banner" class="cookie-banner" role="dialog" aria-live="polite" aria-label="Aviso de cookies">
    <p>Usamos cookies para melhorar a experiência, medir audiência e exibir publicidade. Leia nossa
        <a href="<?= e(url_path('/politica-de-privacidade')) ?>">Política de Privacidade</a> e
        <a href="<?= e(url_path('/politica-de-cookies')) ?>">Política de Cookies</a>.
    </p>
    <button id="cookie-accept" class="btn">Aceitar</button>
</div>
<?= render_ads_bootstrap() ?>
<script src="/assets/js/app.js" defer></script>
</body>
</html>
