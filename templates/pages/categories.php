<section class="page-hero">
    <div class="page-hero-inner">
        <p class="section-kicker">Vagas RJ</p>
        <h1>Categorias de vagas</h1>
        <p>Escolha a área profissional e encontre oportunidades relevantes no Rio de Janeiro.</p>
    </div>
</section>

<div class="entity-grid">
    <?php foreach ($categories as $category): ?>
        <article class="entity-card">
            <h2><a href="<?= e(category_public_path($category['slug'])) ?>"><?= e($category['name']) ?></a></h2>
            <p>Vagas ativas de <?= e($category['name']) ?> no estado do Rio de Janeiro.</p>
            <?php if (!empty($category['jobs_count'])): ?>
                <p class="entity-meta"><?= (int) $category['jobs_count'] ?> vaga(s)</p>
            <?php endif; ?>
            <a class="btn btn-sm btn-outline" href="<?= e(category_public_path($category['slug'])) ?>">Explorar categoria</a>
        </article>
    <?php endforeach; ?>
</div>
