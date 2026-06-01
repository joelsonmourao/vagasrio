<section class="page-hero">
    <div class="page-hero-inner">
        <p class="section-kicker">Vagas RJ</p>
        <h1>Empresas com vagas no Rio de Janeiro</h1>
        <p>Conheça empresas que publicam oportunidades no estado e encontre vagas alinhadas ao seu perfil.</p>
    </div>
</section>

<?php if (empty($companies)): ?>
    <div class="empty-state empty-state-large">
        <h3>Nenhuma empresa cadastrada</h3>
        <p>Volte em breve para novas oportunidades.</p>
    </div>
<?php else: ?>
    <div class="entity-grid">
        <?php foreach ($companies as $company): ?>
            <article class="entity-card">
                <h2><a href="<?= e(base_url('/empresa/' . $company['slug'])) ?>"><?= e($company['name']) ?></a></h2>
                <p><?= e(excerpt((string) ($company['description'] ?? 'Empresa com vagas abertas no Rio de Janeiro.'), 140)) ?></p>
                <a class="btn btn-sm btn-outline" href="<?= e(base_url('/empresa/' . $company['slug'])) ?>">Ver vagas</a>
            </article>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
