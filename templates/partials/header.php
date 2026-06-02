<header class="site-header">
    <div class="container header-inner">
        <a class="logo" href="/">
            <img class="logo-image" src="<?= e(url_path('/assets/img/logo-vagas-rj.svg')) ?>" width="220" height="42" alt="<?= e(config('site.name')) ?> — <?= e(config('site.subtitle', 'Empregos no Rio de Janeiro')) ?>">
        </a>

        <button class="nav-toggle" type="button" aria-expanded="false" aria-controls="main-nav-panel" data-nav-toggle>
            <span></span><span></span><span></span>
            <span class="sr-only">Abrir menu</span>
        </button>

        <nav class="main-nav" id="main-nav-panel">
            <a class="<?= is_active_menu('/') ? 'active' : '' ?>" href="/">Início</a>
            <a class="<?= is_active_menu('/vagas') ? 'active' : '' ?>" href="/vagas">Vagas</a>
            <a class="<?= (is_active_menu('/cidades') || is_active_menu('/cidade')) ? 'active' : '' ?>" href="/cidades">Cidades</a>
            <a class="<?= is_active_menu('/empresas') ? 'active' : '' ?>" href="/empresas">Empresas</a>
            <a class="<?= is_active_menu('/categorias') ? 'active' : '' ?>" href="/categorias">Categorias</a>
            <a class="<?= is_active_menu('/blog') ? 'active' : '' ?>" href="/blog">Blog</a>
            <a class="btn btn-nav" href="/vagas">Ver vagas</a>
        </nav>
    </div>
</header>
