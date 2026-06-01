<section class="page-hero">
    <div class="page-hero-inner">
        <p class="section-kicker">Categoria · RJ</p>
        <h1>Vagas de <?= e($category['name']) ?></h1>
        <p>Oportunidades da área de <?= e($category['name']) ?> no Rio de Janeiro.</p>
    </div>
</section>

<?php if (empty($jobsData['jobs'])): ?>
    <div class="empty-state empty-state-large">
        <h3>Nenhuma vaga ativa nesta categoria</h3>
        <p>Explore outras categorias ou veja todas as vagas do estado.</p>
        <a class="btn btn-sm" href="<?= e(base_url('/vagas')) ?>">Ver todas as vagas</a>
    </div>
<?php else: ?>
    <div class="job-grid job-grid-listing">
        <?php foreach ($jobsData['jobs'] as $job): ?>
            <?php require ROOT_PATH . '/templates/partials/job_card.php'; ?>
        <?php endforeach; ?>
    </div>
    <?php $pagination = $jobsData; require ROOT_PATH . '/templates/partials/pagination.php'; ?>
<?php endif; ?>
