<section class="page-hero">
    <div class="page-hero-inner">
        <p class="section-kicker">Blog · Vagas RJ</p>
        <h1>Blog e carreira</h1>
        <p>Conteúdo original sobre currículo, entrevistas, mercado de trabalho no Rio de Janeiro e segurança na candidatura.</p>
    </div>
</section>

<?php if (!empty($blogCategories)): ?>
<section class="panel panel-compact">
    <h2 class="sr-only">Categorias do blog</h2>
    <div class="chip-grid">
        <a class="chip is-active" href="<?= e(url_path('/blog')) ?>">Todos</a>
        <?php foreach ($blogCategories as $cat): ?>
            <a class="chip" href="<?= e(blog_category_public_path($cat['slug'])) ?>"><?= e($cat['name']) ?> (<?= (int) ($cat['posts_count'] ?? 0) ?>)</a>
        <?php endforeach; ?>
    </div>
</section>
<?php endif; ?>

<?php if (!empty($articles)): ?>
    <?= ad_slot('blog_after_intro', 'blog', 970, 120) ?>
<?php endif; ?>

<?php if (empty($articles)): ?>
    <div class="empty-state empty-state-large">
        <h3>Nenhum artigo publicado</h3>
        <p>Em breve teremos conteúdos sobre carreira e emprego no RJ.</p>
    </div>
<?php else: ?>
    <div class="entity-grid blog-grid">
        <?php foreach ($articles as $i => $article): ?>
            <?php require ROOT_PATH . '/templates/partials/blog_card.php'; ?>
            <?php if ($i === 5): ?>
                <?= ad_slot('blog_listing_inline', 'blog', 970, 100) ?>
            <?php endif; ?>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
