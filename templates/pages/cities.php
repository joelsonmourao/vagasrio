<section class="page-hero">
    <div class="page-hero-inner">
        <p class="section-kicker">Vagas RJ</p>
        <h1>Cidades do Rio de Janeiro</h1>
        <p>Explore oportunidades de emprego por município do estado do RJ.</p>
    </div>
</section>

<?php if (empty($cities)): ?>
    <div class="empty-state empty-state-large">
        <h3>Nenhuma cidade cadastrada</h3>
        <p>Em breve novas cidades estarão disponíveis.</p>
    </div>
<?php else: ?>
    <div class="city-grid">
        <?php foreach ($cities as $city): ?>
            <article class="city-card">
                <div class="city-card-head">
                    <h2><a href="<?= e(city_public_path($city['slug'])) ?>"><?= e($city['name']) ?></a></h2>
                    <span class="city-badge"><?= (int) ($city['jobs_count'] ?? 0) ?> vaga(s)</span>
                </div>
                <p>Vagas de emprego em <?= e($city['name']) ?>/RJ.</p>
                <a class="btn btn-sm btn-outline" href="<?= e(city_public_path($city['slug'])) ?>">Ver vagas</a>
            </article>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
