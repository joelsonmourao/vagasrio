<article class="entity-card blog-card">
    <?php if (!empty($article['category_name'])): ?>
        <p class="blog-card-category"><?= e($article['category_name']) ?></p>
    <?php endif; ?>
    <h2><a href="<?= e(url_path('/blog/' . $article['slug'])) ?>"><?= e($article['title']) ?></a></h2>
    <p><?= e($article['excerpt']) ?></p>
    <a class="btn btn-sm btn-outline" href="<?= e(url_path('/blog/' . $article['slug'])) ?>">Ler artigo</a>
</article>
