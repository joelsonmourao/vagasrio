<?php $mainArticle = $article; ?>
<main class="article-page">
    <nav class="breadcrumb" aria-label="Breadcrumb">
        <a href="<?= e(url_path('/')) ?>">Início</a>
        <span aria-hidden="true">›</span>
        <a href="<?= e(url_path('/blog')) ?>">Blog</a>
        <?php if (!empty($mainArticle['category_name'])): ?>
            <span aria-hidden="true">›</span>
            <a href="<?= e(blog_category_public_path((string) $mainArticle['category_slug'])) ?>"><?= e($mainArticle['category_name']) ?></a>
        <?php endif; ?>
        <span aria-hidden="true">›</span>
        <span aria-current="page"><?= e(excerpt($mainArticle['title'], 60)) ?></span>
    </nav>

    <div class="article-page-grid">
        <article class="article-content">
            <header class="article-header panel">
                <?php if (!empty($mainArticle['category_name'])): ?>
                    <p class="article-meta-kicker"><a href="<?= e(blog_category_public_path((string) $mainArticle['category_slug'])) ?>"><?= e($mainArticle['category_name']) ?></a></p>
                <?php endif; ?>
                <h1><?= e($mainArticle['title']) ?></h1>
                <p class="article-excerpt"><?= e($mainArticle['excerpt']) ?></p>
                <p class="article-date">Publicado em <?= e(format_date_br($mainArticle['published_at'])) ?></p>
            </header>

            <div class="panel prose article-prose"><?= article_content_with_mid_ad((string) $mainArticle['content'], 'article') ?></div>

            <?= ad_slot('blog_middle', 'article', 970, 110) ?>

            <section class="panel section-block article-read-more">
                <div class="section-head section-head-inline">
                    <div>
                        <p class="section-kicker">Leia também</p>
                        <h2>Mais conteúdo para candidatos no RJ</h2>
                    </div>
                    <?php if (!empty($mainArticle['category_name'])): ?>
                        <a class="section-link" href="<?= e(blog_category_public_path((string) $mainArticle['category_slug'])) ?>">Ver categoria →</a>
                    <?php endif; ?>
                </div>
                <?php if (!empty($relatedArticles)): ?>
                    <div class="entity-grid blog-grid blog-grid-compact">
                        <?php foreach (array_slice($relatedArticles, 0, 3) as $article): ?>
                            <?php require ROOT_PATH . '/templates/partials/blog_card.php'; ?>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>

            <?= ad_slot('blog_after_content', 'article', 970, 110) ?>
        </article>

        <aside class="article-sidebar">
            <?php if (!empty($relatedArticles)): ?>
                <section class="panel panel-compact sidebar-card">
                    <h2>Artigos relacionados</h2>
                    <ul class="sidebar-link-list">
                        <?php foreach ($relatedArticles as $related): ?>
                            <li><a href="<?= e(url_path('/blog/' . $related['slug'])) ?>"><?= e($related['title']) ?></a></li>
                        <?php endforeach; ?>
                    </ul>
                </section>
            <?php endif; ?>

            <?php if (!empty($blogCategories)): ?>
                <section class="panel panel-compact sidebar-card">
                    <h2>Categorias do blog</h2>
                    <ul class="sidebar-link-list">
                        <?php foreach ($blogCategories as $cat): ?>
                            <li><a href="<?= e(blog_category_public_path($cat['slug'])) ?>"><?= e($cat['name']) ?></a></li>
                        <?php endforeach; ?>
                    </ul>
                </section>
            <?php endif; ?>

            <?= ad_slot('article_sidebar', 'article', 300, 280) ?>
        </aside>
    </div>
</main>
