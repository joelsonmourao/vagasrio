<section class="page-hero">
    <div class="page-hero-inner">
        <p class="section-kicker">Categoria · RJ</p>
        <h1>Vagas de <?= e($category['name']) ?></h1>
        <p class="section-text"><?= e(category_page_intro((string) $category['name'])) ?></p>
        <?php if (($jobsData['total'] ?? 0) > 0): ?>
            <p class="page-hero-count"><?= (int) $jobsData['total'] ?> vaga(s) ativa(s)</p>
        <?php endif; ?>
    </div>
</section>

<?= ad_slot('category_inline', 'category', 970, 100) ?>

<?php if (empty($jobsData['jobs'])): ?>
    <div class="empty-state empty-state-large">
        <h3>Nenhuma vaga ativa nesta categoria</h3>
        <p>Explore outras categorias ou veja todas as vagas do estado.</p>
        <a class="btn btn-sm" href="<?= e(url_path('/vagas')) ?>">Ver todas as vagas</a>
    </div>
<?php else: ?>
    <div class="job-grid job-grid-listing">
        <?php foreach ($jobsData['jobs'] as $job): ?>
            <?php require ROOT_PATH . '/templates/partials/job_card.php'; ?>
        <?php endforeach; ?>
    </div>
    <?php $pagination = $jobsData; require ROOT_PATH . '/templates/partials/pagination.php'; ?>
<?php endif; ?>

<section class="panel section-block">
    <div class="section-head section-head-inline">
        <div>
            <h2>Cidades com vagas de <?= e($category['name']) ?></h2>
        </div>
        <a class="section-link" href="<?= e(url_path('/cidades')) ?>">Ver cidades →</a>
    </div>
    <div class="chip-grid">
        <?php foreach ($cities ?? [] as $cityItem): ?>
            <a class="chip" href="<?= e(url_path('/vagas?category=' . rawurlencode($category['slug']) . '&city=' . rawurlencode($cityItem['slug']))) ?>"><?= e($cityItem['name']) ?></a>
        <?php endforeach; ?>
    </div>
</section>

<?php require ROOT_PATH . '/templates/partials/related_articles.php'; ?>
