<section class="page-hero">
    <div class="page-hero-inner">
        <p class="section-kicker">Vagas RJ · <?= e($city['name']) ?></p>
        <h1>Vagas em <?= e($city['name']) ?> RJ</h1>
        <p class="section-text"><?= e(city_page_intro((string) $city['name'])) ?></p>
        <?php if (($jobsData['total'] ?? 0) > 0): ?>
            <p class="page-hero-count"><?= (int) $jobsData['total'] ?> vaga(s) ativa(s)</p>
        <?php endif; ?>
    </div>
</section>

<?= ad_slot('city_inline', 'city', 970, 100) ?>

<?php if (empty($jobsData['jobs'])): ?>
    <div class="empty-state empty-state-large">
        <h3>Nenhuma vaga ativa nesta cidade</h3>
        <p>Volte em breve ou explore vagas em outras cidades do Rio de Janeiro.</p>
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
            <h2>Categorias de vagas</h2>
            <p class="section-text">Filtre oportunidades em <?= e($city['name']) ?> por área profissional.</p>
        </div>
        <a class="section-link" href="<?= e(url_path('/categorias')) ?>">Ver todas →</a>
    </div>
    <div class="chip-grid">
        <?php foreach ($categories ?? [] as $cat): ?>
            <a class="chip" href="<?= e(url_path('/vagas?city=' . rawurlencode($city['slug']))) ?>"><?= e($cat['name']) ?></a>
        <?php endforeach; ?>
    </div>
</section>

<?php require ROOT_PATH . '/templates/partials/related_articles.php'; ?>

<section class="panel section-block">
    <div class="section-head section-head-inline">
        <div>
            <h2>Outras cidades do RJ</h2>
            <p class="section-text">Navegue por municípios próximos e amplie suas chances de contratação.</p>
        </div>
        <a class="section-link" href="<?= e(url_path('/cidades')) ?>">Ver todas →</a>
    </div>
    <div class="chip-grid">
        <?php foreach ($cities as $cityItem): ?>
            <?php if ($cityItem['slug'] === $city['slug']) continue; ?>
            <a class="chip" href="<?= e(city_public_path($cityItem['slug'])) ?>"><?= e($cityItem['name']) ?></a>
        <?php endforeach; ?>
    </div>
</section>
