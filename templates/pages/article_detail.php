<article class="article article-layout">
    <header class="page-hero page-hero-compact">
        <div class="page-hero-inner">
            <?php if (!empty($article['category_name'])): ?>
                <p class="section-kicker"><a href="<?= e(blog_category_public_path((string) $article['category_slug'])) ?>"><?= e($article['category_name']) ?></a></p>
            <?php else: ?>
                <p class="section-kicker">Blog · Vagas RJ</p>
            <?php endif; ?>
            <h1><?= e($article['title']) ?></h1>
            <p><?= e($article['excerpt']) ?></p>
        </div>
    </header>

    <div class="article-layout-grid">
        <div class="article-main">
            <?= ad_slot('blog_after_intro', 'article', 970, 110) ?>

            <div class="panel prose article-prose"><?= $article['content'] ?></div>

            <?= ad_slot('blog_middle', 'article', 970, 110) ?>

            <?php require ROOT_PATH . '/templates/partials/related_articles.php'; ?>

            <?= ad_slot('blog_after_content', 'article', 970, 110) ?>
        </div>
        <aside class="article-sidebar article-sidebar-desktop">
            <?= ad_slot('article_sidebar', 'article', 300, 280) ?>
        </aside>
    </div>
</article>
