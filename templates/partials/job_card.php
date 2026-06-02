<article class="job-card job-card-premium">
    <div class="job-card-accent" aria-hidden="true"></div>
    <div class="job-card-body">
        <div class="job-card-top">
            <?php if (!empty($job['category_name'])): ?>
                <span class="tag tag-category"><?= e($job['category_name']) ?></span>
            <?php else: ?>
                <span class="tag tag-muted">Vaga</span>
            <?php endif; ?>
            <time datetime="<?= e(format_datetime_iso_attr($job['published_at'])) ?>"><?= e(format_date_br($job['published_at'])) ?></time>
        </div>
        <h3><a href="<?= e(url_path('/vagas/' . $job['slug'])) ?>"><?= e($job['title']) ?></a></h3>
        <div class="job-card-meta">
            <span class="job-card-company"><?= e($job['company_name']) ?></span>
            <span class="job-card-location"><span class="loc-dot" aria-hidden="true"></span><?= e($job['city_name']) ?>/RJ</span>
        </div>
        <?php if (!empty($job['employment_type'])): ?>
            <p class="job-card-type"><?= e(employment_type_label($job['employment_type'])) ?></p>
        <?php endif; ?>
        <p class="job-card-excerpt"><?= e(excerpt($job['description'], 155)) ?></p>
        <div class="job-card-footer">
            <?php if (!empty($job['salary'])): ?>
                <span class="job-salary"><?= e($job['salary']) ?></span>
            <?php endif; ?>
            <a class="btn btn-sm btn-card" href="<?= e(url_path('/vagas/' . $job['slug'])) ?>">Ver vaga</a>
        </div>
    </div>
</article>
