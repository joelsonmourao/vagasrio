<section class="page-hero">
    <div class="page-hero-inner">
        <p class="section-kicker">Empresa · RJ</p>
        <h1><?= e($company['name']) ?></h1>
        <p><?= e((string) ($company['description'] ?: 'Oportunidades publicadas no Vagas RJ para candidatos do Rio de Janeiro.')) ?></p>
    </div>
</section>

<section class="panel panel-warning panel-security panel-security-inline">
    <div class="panel-security-icon" aria-hidden="true">!</div>
    <div>
        <h2>Aviso importante</h2>
        <p><?= e(company_page_disclaimer()) ?></p>
    </div>
</section>

<?= ad_slot('company_inline', 'company', 970, 100) ?>

<?php if (empty($jobsData['jobs'])): ?>
    <div class="empty-state empty-state-large">
        <h3>Sem vagas ativas no momento</h3>
        <p>Esta empresa não possui oportunidades abertas agora. Explore outras empresas do Rio de Janeiro.</p>
        <a class="btn btn-sm" href="<?= e(url_path('/empresas')) ?>">Ver empresas</a>
    </div>
<?php else: ?>
    <div class="section-head">
        <h2>Vagas abertas</h2>
        <p class="section-text"><?= (int) $jobsData['total'] ?> oportunidade(s) disponível(is)</p>
    </div>
    <div class="job-grid job-grid-listing">
        <?php foreach ($jobsData['jobs'] as $job): ?>
            <?php require ROOT_PATH . '/templates/partials/job_card.php'; ?>
        <?php endforeach; ?>
    </div>
    <?php $pagination = $jobsData; require ROOT_PATH . '/templates/partials/pagination.php'; ?>
<?php endif; ?>
