<?php if (empty($relatedArticles)): return; endif; ?>
<section class="panel section-block related-articles">
    <div class="section-head section-head-inline">
        <div>
            <p class="section-kicker">Blog</p>
            <h2>Artigos relacionados</h2>
        </div>
        <a class="section-link" href="<?= e(url_path('/blog')) ?>">Ver blog →</a>
    </div>
    <div class="entity-grid blog-grid blog-grid-compact">
        <?php foreach ($relatedArticles as $article): ?>
            <?php require ROOT_PATH . '/templates/partials/blog_card.php'; ?>
        <?php endforeach; ?>
    </div>
</section>
