<article class="article">
    <header class="page-hero page-hero-compact">
        <div class="page-hero-inner">
            <p class="section-kicker">Blog · Vagas RJ</p>
            <h1><?= e($article['title']) ?></h1>
            <p><?= e($article['excerpt']) ?></p>
        </div>
    </header>

    <?= ad_slot('blog_after_intro', 'article', 970, 110) ?>

    <div class="panel prose article-prose"><?= $article['content'] ?></div>

    <?= ad_slot('blog_middle', 'article', 970, 110) ?>

    <section class="panel section-block">
        <h2>Dicas relacionadas</h2>
        <ul class="tips-list">
            <li>Atualize seu currículo com resultados concretos.</li>
            <li>Pesquise a empresa antes da entrevista.</li>
            <li>Confira novas vagas diariamente na sua cidade do RJ.</li>
        </ul>
    </section>

    <?= ad_slot('blog_after_content', 'article', 970, 110) ?>
</article>
