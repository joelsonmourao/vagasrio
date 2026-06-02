<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($title ?? 'Admin') ?> - <?= e(config('site.name')) ?></title>
    <meta name="robots" content="noindex,nofollow">
    <?php require ROOT_PATH . '/templates/partials/head_icons.php'; ?>
    <link rel="stylesheet" href="/assets/css/app.css">
</head>
<body class="admin-body">
<div class="admin-nav-backdrop" id="admin-nav-backdrop" aria-hidden="true"></div>
<?php
$adminPath = rtrim((string) parse_url((string) ($_SERVER['REQUEST_URI'] ?? '/admin'), PHP_URL_PATH), '/') ?: '/admin';
$isLoginPage = ($adminPath === '/admin/login');
?>
<header class="admin-header">
    <div class="admin-shell admin-header-inner">
        <?php if (!$isLoginPage): ?>
        <a class="admin-brand" href="<?= e(url_path('/admin')) ?>">
            <img class="admin-brand-logo" src="<?= e(url_path('/assets/img/logo-vagas-rj.svg')) ?>" width="196" height="38" alt="<?= e(config('site.name')) ?> — Painel administrativo">
        </a>
        <button class="admin-nav-toggle" type="button" aria-expanded="false" aria-controls="admin-nav">Menu</button>
        <nav id="admin-nav" class="admin-nav">
            <?php foreach ([
                '/admin' => 'Dashboard',
                '/admin/jobs' => 'Vagas',
                '/admin/companies' => 'Empresas',
                '/admin/categories' => 'Categorias',
                '/admin/blog/posts' => 'Blog',
                '/admin/import' => 'Importação',
            ] as $href => $label): ?>
                <?php
                $isActive = $href === '/admin'
                    ? ($adminPath === '/admin' || $adminPath === '/admin/dashboard')
                    : ($href === '/admin/blog/posts'
                        ? str_starts_with($adminPath, '/admin/blog')
                        : str_starts_with($adminPath, $href));
                ?>
                <a class="admin-nav-link<?= $isActive ? ' is-active' : '' ?>" href="<?= e(url_path($href === '/admin' ? '/admin' : $href)) ?>"><?= e($label) ?></a>
            <?php endforeach; ?>
            <a class="admin-nav-link admin-nav-link-muted" href="<?= e(url_path('/')) ?>" target="_blank" rel="noopener">Ver site</a>
            <a class="admin-nav-link admin-nav-link-danger" href="<?= e(url_path('/admin/logout')) ?>">Sair</a>
        </nav>
        <?php else: ?>
        <a class="admin-brand" href="<?= e(url_path('/')) ?>">
            <img class="admin-brand-logo" src="<?= e(url_path('/assets/img/logo-vagas-rj.svg')) ?>" width="196" height="38" alt="<?= e(config('site.name')) ?>">
        </a>
        <?php endif; ?>
    </div>
</header>
<main class="admin-shell admin-main">
    <?= $content ?>
</main>
<script src="<?= e(url_path('/assets/js/admin.js')) ?>" defer></script>
</body>
</html>
