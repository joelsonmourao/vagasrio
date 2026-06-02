<section class="hero hero-home">
    <div class="hero-pattern" aria-hidden="true"></div>
    <div class="hero-copy">
        <p class="hero-kicker">Portal de vagas no Rio de Janeiro</p>
        <h1>Oportunidades de emprego no estado do Rio de Janeiro</h1>
        <p>O Vagas RJ divulga vagas reais por cargo, empresa e cidade. Busque, compare descrições e candidate-se pelos canais oficiais das empresas.</p>
        <form class="search-form search-form-hero" action="<?= e(url_path('/vagas')) ?>" method="get">
            <label class="sr-only" for="home-q">Buscar vaga</label>
            <input id="home-q" type="text" name="q" placeholder="Cargo, empresa ou palavra-chave">
            <label class="sr-only" for="home-city">Cidade</label>
            <select id="home-city" name="city">
                <option value="">Todas as cidades do RJ</option>
                <?php foreach ($cities as $city): ?>
                    <option value="<?= e($city['slug']) ?>"><?= e($city['name']) ?></option>
                <?php endforeach; ?>
            </select>
            <button class="btn btn-hero btn-accent" type="submit">Buscar vagas</button>
        </form>
    </div>
    <div class="hero-stats">
        <article>
            <strong><?= (int) ($stats['active_jobs'] ?? 0) ?></strong>
            <span>Vagas ativas</span>
        </article>
        <article>
            <strong><?= (int) ($stats['cities'] ?? 0) ?></strong>
            <span>Cidades atendidas</span>
        </article>
        <article>
            <strong><?= (int) ($stats['companies'] ?? 0) ?></strong>
            <span>Empresas com vagas</span>
        </article>
    </div>
</section>

<?= ad_slot('home_after_hero', 'home', 970, 100) ?>

<section class="home-section home-section-jobs">
    <div class="section-head">
        <div>
            <p class="section-kicker">Oportunidades recentes</p>
            <h2>Vagas em destaque no RJ</h2>
        </div>
        <a class="section-link" href="<?= e(url_path('/vagas')) ?>">Ver todas as vagas →</a>
    </div>
    <?php if (empty($recentJobs)): ?>
        <div class="empty-state">
            <p>Nenhuma vaga ativa no momento. Volte em breve.</p>
        </div>
    <?php else: ?>
        <div class="job-grid job-grid-home">
            <?php foreach ($recentJobs as $job): ?>
                <?php require ROOT_PATH . '/templates/partials/job_card.php'; ?>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>

<?= ad_slot('home_between_sections', 'home', 970, 100) ?>

<section class="home-section home-section-soft">
    <div class="section-head section-head-inline">
        <div>
            <p class="section-kicker">Navegação regional</p>
            <h2>Cidades em destaque</h2>
        </div>
        <a class="section-link" href="<?= e(url_path('/cidades')) ?>">Ver todas →</a>
    </div>
    <p class="section-text">Explore vagas nas principais cidades do estado do Rio de Janeiro.</p>
    <div class="city-grid city-grid-compact">
        <?php foreach (array_slice($cities, 0, 8) as $city): ?>
            <a class="city-card city-card-link" href="<?= e(city_public_path($city['slug'])) ?>">
                <strong><?= e($city['name']) ?></strong>
                <span><?= (int) ($city['jobs_count'] ?? 0) ?> vaga(s)</span>
            </a>
        <?php endforeach; ?>
    </div>
</section>

<section class="home-section home-section-soft">
    <div class="section-head section-head-inline">
        <div>
            <p class="section-kicker">Áreas profissionais</p>
            <h2>Categorias de vagas</h2>
        </div>
        <a class="section-link" href="<?= e(url_path('/categorias')) ?>">Ver categorias →</a>
    </div>
    <div class="category-grid">
        <?php foreach ($categories as $category): ?>
            <a class="category-card" href="<?= e(category_public_path($category['slug'])) ?>">
                <span class="category-card-label"><?= e($category['name']) ?></span>
                <span class="category-card-cta">Explorar →</span>
            </a>
        <?php endforeach; ?>
    </div>
</section>

<section class="home-section">
    <div class="section-head section-head-inline">
        <div>
            <p class="section-kicker">Como funciona</p>
            <h2>Encontre sua vaga em 3 passos</h2>
        </div>
    </div>
    <div class="steps-grid steps-grid-enhanced">
        <article class="panel panel-step">
            <span class="step-num">1</span>
            <h3>Busque</h3>
            <p>Encontre vagas por cargo, empresa, cidade ou categoria no RJ.</p>
        </article>
        <article class="panel panel-step">
            <span class="step-num">2</span>
            <h3>Confira</h3>
            <p>Leia a descrição completa da vaga antes de se candidatar.</p>
        </article>
        <article class="panel panel-step">
            <span class="step-num">3</span>
            <h3>Candidate-se</h3>
            <p>Acesse o link oficial informado pela empresa contratante.</p>
        </article>
    </div>
</section>

<section class="home-section home-section-security panel panel-warning panel-security">
    <div class="panel-security-icon" aria-hidden="true">!</div>
    <div>
        <h2>Aviso de segurança para candidatos</h2>
        <p>Desconfie de pedidos de pagamento, depósito ou compartilhamento de dados sensíveis. O Vagas RJ apenas divulga oportunidades e não participa do processo seletivo. <a href="<?= e(url_path('/seguranca-para-candidatos')) ?>">Saiba como se proteger →</a></p>
    </div>
</section>

<?php if (!empty($recentArticles)): ?>
<section class="home-section">
    <div class="section-head section-head-inline">
        <div>
            <p class="section-kicker">Blog Vagas RJ</p>
            <h2>Dicas para sua carreira no RJ</h2>
        </div>
        <a class="section-link" href="<?= e(url_path('/blog')) ?>">Ver blog →</a>
    </div>
    <div class="entity-grid blog-grid blog-grid-compact">
        <?php foreach ($recentArticles as $article): ?>
            <?php require ROOT_PATH . '/templates/partials/blog_card.php'; ?>
        <?php endforeach; ?>
    </div>
</section>
<?php endif; ?>
