<section class="page-hero page-hero-listing">
    <div class="page-hero-inner">
        <p class="section-kicker">Vagas RJ</p>
        <h1>Vagas no Rio de Janeiro</h1>
        <p>Encontre oportunidades em todo o estado do RJ com filtros por cidade, empresa e categoria.</p>
        <?php if (($jobsData['total'] ?? 0) > 0): ?>
            <p class="page-hero-count"><?= (int) $jobsData['total'] ?> vaga(s) encontrada(s)</p>
        <?php endif; ?>
    </div>
</section>

<section class="filters-panel">
    <h2 class="filters-title">Filtrar vagas</h2>
    <form class="search-form filters" method="get" action="<?= e(url_path('/vagas')) ?>">
        <input type="text" name="q" value="<?= e((string) ($filters['q'] ?? '')) ?>" placeholder="Cargo, empresa ou palavra-chave">
        <select name="city">
            <option value="">Cidade</option>
            <?php foreach ($cities as $city): ?>
                <option value="<?= e($city['slug']) ?>" <?= (($filters['city'] ?? '') === $city['slug']) ? 'selected' : '' ?>><?= e($city['name']) ?></option>
            <?php endforeach; ?>
        </select>
        <select name="company">
            <option value="">Empresa</option>
            <?php foreach ($companies as $company): ?>
                <option value="<?= e($company['slug']) ?>" <?= (($filters['company'] ?? '') === $company['slug']) ? 'selected' : '' ?>><?= e($company['name']) ?></option>
            <?php endforeach; ?>
        </select>
        <select name="category">
            <option value="">Categoria</option>
            <?php foreach ($categories as $category): ?>
                <option value="<?= e($category['slug']) ?>" <?= (($filters['category'] ?? '') === $category['slug']) ? 'selected' : '' ?>><?= e($category['name']) ?></option>
            <?php endforeach; ?>
        </select>
        <button class="btn btn-accent" type="submit">Filtrar vagas</button>
    </form>
</section>

<div class="listing-layout">
    <div class="listing-main">
        <?php if (empty($jobsData['jobs'])): ?>
            <div class="empty-state empty-state-large">
                <h3>Nenhuma vaga encontrada</h3>
                <p>Tente ajustar os filtros ou buscar por outro termo.</p>
                <a class="btn btn-sm" href="<?= e(url_path('/vagas')) ?>">Limpar filtros</a>
            </div>
        <?php else: ?>
            <div class="job-grid job-grid-listing">
                <?php foreach ($jobsData['jobs'] as $i => $job): ?>
                    <?php require ROOT_PATH . '/templates/partials/job_card.php'; ?>
                    <?php if ($i === 2): ?>
                        <?= ad_slot('listing_inline', 'jobs', 970, 100) ?>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
            <?php $pagination = $jobsData; require ROOT_PATH . '/templates/partials/pagination.php'; ?>
        <?php endif; ?>
    </div>
    <?php if (!empty($jobsData['jobs'])): ?>
        <aside class="listing-sidebar">
            <?= ad_slot('listing_sidebar', 'jobs', 300, 280) ?>
        </aside>
    <?php endif; ?>
</div>
