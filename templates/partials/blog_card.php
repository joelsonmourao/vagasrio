<article class="entity-card blog-card blog-card-list">
    <?php if (!empty($article['category_name'])): ?>
        <p class="blog-card-category"><?= e($article['category_name']) ?></p>
    <?php endif; ?>
    <h2><a href="<?= e(url_path('/blog/' . $article['slug'])) ?>"><?= e($article['title']) ?></a></h2>
    <?php if (!empty($article['published_at'])): ?>
        <p class="blog-card-date"><?= e(format_date_br($article['published_at'])) ?></p>
    <?php endif; ?>
    <p><?= e($article['excerpt']) ?></p>
    <a class="btn btn-sm btn-outline" href="<?= e(url_path('/blog/' . $article['slug'])) ?>">Ler artigo</a>
</article>
