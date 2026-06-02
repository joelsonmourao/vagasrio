<?php $mainJob = $job; ?>
<?php $applyMeta = !empty($mainJob['apply_url']) ? apply_button_meta((string) $mainJob['apply_url']) : null; ?>
<article class="job-detail">
    <header class="job-detail-hero">
        <div class="job-detail-hero-inner">
            <div class="job-detail-title-wrap">
                <?php if (!empty($mainJob['category_name'])): ?>
                    <span class="tag tag-lg tag-category"><?= e($mainJob['category_name']) ?></span>
                <?php endif; ?>
                <h1><?= e($mainJob['title']) ?></h1>
                <p class="job-detail-lead">
                    <span class="job-detail-company"><?= e($mainJob['company_name']) ?></span>
                    <span class="job-detail-sep" aria-hidden="true">·</span>
                    <span class="job-detail-city"><?= e($mainJob['city_name']) ?>/RJ</span>
                </p>
            </div>
            <div class="job-detail-hero-meta">
                <span>Publicada em <?= e(format_date_br($mainJob['published_at'])) ?></span>
                <?php if (!empty($mainJob['employment_type'])): ?>
                    <span><?= e(employment_type_label($mainJob['employment_type'])) ?></span>
                <?php endif; ?>
                <?php if (!empty($mainJob['salary'])): ?>
                    <span><?= e($mainJob['salary']) ?></span>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <div class="job-detail-layout">
        <div class="job-detail-main">
            <section class="panel panel-description">
                <h2>Descrição da vaga</h2>
                <div class="prose job-description"><?= format_job_description($mainJob['description']) ?></div>
            </section>

            <?= ad_slot('job_after_main', 'job_detail', 970, 110, 'ad-slot-job-after-main') ?>

            <section class="panel panel-warning panel-security panel-security-inline">
                <div class="panel-security-icon" aria-hidden="true">!</div>
                <div>
                    <h2>Aviso de segurança</h2>
                    <p>O Vagas RJ apenas divulga oportunidades e não participa do processo seletivo. Desconfie de pedidos de pagamento para candidatura e confirme as informações no site oficial da empresa.</p>
                </div>
            </section>
        </div>

        <aside class="job-aside">
            <section class="job-apply-box">
                <p class="apply-kicker">Próximo passo</p>
                <h2>Candidatura</h2>
                <?php if ($applyMeta): ?>
                    <a class="btn btn-lg btn-full btn-accent" href="<?= e($applyMeta['href']) ?>"<?= $applyMeta['target'] ? ' target="' . e($applyMeta['target']) . '"' : '' ?> rel="<?= e($applyMeta['rel']) ?>"><?= e(apply_button_label((string) $mainJob['apply_url'])) ?></a>
                    <p class="apply-note"><?= e($applyMeta['note']) ?></p>
                <?php else: ?>
                    <p class="empty-state">Link ou e-mail de candidatura não informado pela empresa.</p>
                <?php endif; ?>
            </section>

            <section class="panel panel-compact panel-summary">
                <h3>Resumo da vaga</h3>
                <ul class="summary-list">
                    <li><span>Empresa</span><strong><?= e($mainJob['company_name']) ?></strong></li>
                    <li><span>Cidade</span><strong><?= e($mainJob['city_name']) ?>/RJ</strong></li>
                    <?php if (!empty($mainJob['category_name'])): ?>
                        <li><span>Categoria</span><strong><?= e($mainJob['category_name']) ?></strong></li>
                    <?php endif; ?>
                    <?php if (!empty($mainJob['employment_type'])): ?>
                        <li><span>Contratação</span><strong><?= e(employment_type_label($mainJob['employment_type'])) ?></strong></li>
                    <?php endif; ?>
                    <?php if (!empty($mainJob['salary'])): ?>
                        <li><span>Salário</span><strong><?= e($mainJob['salary']) ?></strong></li>
                    <?php endif; ?>
                    <li><span>Publicação</span><strong><?= e(format_date_br($mainJob['published_at'])) ?></strong></li>
                    <?php if (!empty($mainJob['valid_through'])): ?>
                        <li><span>Validade</span><strong><?= e(format_date_br($mainJob['valid_through'])) ?></strong></li>
                    <?php endif; ?>
                </ul>
            </section>

            <div class="job-aside-ad job-aside-ad-desktop">
                <?= ad_slot('job_sidebar', 'job_detail', 300, 280) ?>
            </div>
        </aside>
    </div>

    <?php if (!empty($relatedArticles)): ?>
        <?php require ROOT_PATH . '/templates/partials/related_articles.php'; ?>
    <?php endif; ?>

    <?php if (!empty($relatedJobs)): ?>
        <?= ad_slot('job_before_related', 'job_detail', 970, 110, 'ad-slot-job-before-related') ?>
        <section class="related-section">
            <div class="section-head">
                <div>
                    <p class="section-kicker">Veja também</p>
                    <h2>Vagas relacionadas</h2>
                </div>
            </div>
            <div class="job-grid job-grid-home">
                <?php foreach ($relatedJobs as $job): ?>
                    <?php require ROOT_PATH . '/templates/partials/job_card.php'; ?>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endif; ?>
</article>

<?php $jobSchema = build_job_posting_schema($mainJob); ?>
<script type="application/ld+json"><?= encode_json_ld($jobSchema) ?></script>
