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
            <h2><a href="<?= e(url_path('/categoria/' . $category['slug'])) ?>"><?= e($category['name']) ?></a></h2>
            <p>Vagas ativas de <?= e($category['name']) ?> no estado do Rio de Janeiro.</p>
            <a class="btn btn-sm btn-outline" href="<?= e(url_path('/categoria/' . $category['slug'])) ?>">Explorar categoria</a>
        </article>
    <?php endforeach; ?>
</div>
