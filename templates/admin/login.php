<section class="admin-login-wrap">
    <div class="admin-card admin-login-card">
        <div class="admin-login-brand">
            <img class="admin-brand-logo" src="<?= e(url_path('/assets/img/logo-vagas-rj.svg')) ?>" width="196" height="38" alt="<?= e(config('site.name')) ?>">
            <div>
                <h1 class="sr-only"><?= e(config('site.name')) ?></h1>
                <p>Painel administrativo</p>
            </div>
        </div>
        <?php if (!empty($flashError)): ?>
            <div class="admin-alert admin-alert-error" role="alert"><?= e($flashError) ?></div>
        <?php endif; ?>
        <form method="post" action="<?= e(url_path('/admin/login')) ?>" class="admin-form">
            <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
            <label class="admin-field">
                <span>Usuário</span>
                <input type="text" name="username" required autocomplete="username">
            </label>
            <label class="admin-field">
                <span>Senha</span>
                <input type="password" name="password" required autocomplete="current-password">
            </label>
            <div class="admin-form-actions">
                <button class="btn btn-full" type="submit">Entrar</button>
            </div>
        </form>
        <p class="admin-hint">Altere usuário e senha em <code>config/app.php</code> antes de publicar.</p>
    </div>
</section>
