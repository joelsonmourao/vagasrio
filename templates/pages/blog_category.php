<section class="page-hero">
    <div class="page-hero-inner">
        <p class="section-kicker">Blog · <?= e($blogCategory['name']) ?></p>
        <h1><?= e($blogCategory['name']) ?></h1>
        <p><?= e($blogCategory['description']) ?></p>
        <?php if (!empty($articlesData['total'])): ?>
            <p class="page-hero-count"><?= (int) $articlesData['total'] ?> artigo(s)</p>
        <?php endif; ?>
    </div>
</section>

<?php if (!empty($blogCategories)): ?>
<section class="panel panel-compact">
    <h2 class="sr-only">Categorias do blog</h2>
    <div class="chip-grid">
        <a class="chip" href="<?= e(url_path('/blog')) ?>">Todos</a>
        <?php foreach ($blogCategories as $cat): ?>
            <a class="chip<?= ($cat['slug'] === $blogCategory['slug']) ? ' is-active' : '' ?>" href="<?= e(blog_category_public_path($cat['slug'])) ?>"><?= e($cat['name']) ?></a>
        <?php endforeach; ?>
    </div>
</section>
<?php endif; ?>

<?= ad_slot('blog_after_intro', 'blog', 970, 120) ?>

<?php if (empty($articles)): ?>
    <div class="empty-state empty-state-large">
        <h3>Nenhum artigo nesta categoria</h3>
        <p>Explore outras categorias do blog ou volte em breve.</p>
    </div>
<?php else: ?>
    <div class="entity-grid blog-grid blog-grid-list">
        <?php foreach ($articles as $article): ?>
            <?php require ROOT_PATH . '/templates/partials/blog_card.php'; ?>
        <?php endforeach; ?>
    </div>
    <?php
    $pagination = $articlesData ?? [
        'totalPages' => 1,
        'page' => 1,
        'basePath' => '/blog/categoria/' . $blogCategory['slug'],
        'query' => [],
    ];
    require ROOT_PATH . '/templates/partials/pagination.php';
    ?>
<?php endif; ?>
