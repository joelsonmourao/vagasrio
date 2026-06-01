<section class="page-hero">
    <div class="page-hero-inner">
        <p class="section-kicker">Vagas RJ</p>
        <h1>Blog e carreira</h1>
        <p>Dicas de currículo, entrevista e mercado de trabalho no Rio de Janeiro.</p>
    </div>
</section>

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
        <?php foreach ($articles as $article): ?>
            <article class="entity-card blog-card">
                <h2><a href="<?= e(base_url('/blog/' . $article['slug'])) ?>"><?= e($article['title']) ?></a></h2>
                <p><?= e($article['excerpt']) ?></p>
                <a class="btn btn-sm btn-outline" href="<?= e(base_url('/blog/' . $article['slug'])) ?>">Ler artigo</a>
            </article>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
