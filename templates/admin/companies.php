<?php $showForm = !empty($showForm); ?>
<section class="admin-page-head">
    <div>
        <p class="admin-kicker">Cadastros</p>
        <h1>Empresas</h1>
        <p class="admin-lead">Gerencie empresas anunciantes e acompanhe quantas vagas cada uma possui.</p>
    </div>
    <div class="admin-page-actions">
        <a class="btn btn-sm" href="<?= e(url_path('/admin/companies?new=1#company-form')) ?>">Nova empresa</a>
    </div>
</section>

<?php require ROOT_PATH . '/templates/partials/admin_flash.php'; ?>

<section class="admin-card">
    <div class="admin-card-head">
        <h2>Empresas cadastradas</h2>
        <span class="admin-meta"><?= count($companies) ?> empresa(s)</span>
    </div>
    <?php if (empty($companies)): ?>
        <p class="admin-empty">Nenhuma empresa cadastrada.</p>
    <?php else: ?>
        <div class="admin-table-wrap">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Nome</th>
                        <th>Vagas</th>
                        <th>Website</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($companies as $company): ?>
                        <tr>
                            <td><strong><?= e($company['name']) ?></strong></td>
                            <td><?= (int) ($company['jobs_count'] ?? 0) ?></td>
                            <td>
                                <?php if (!empty($company['website'])): ?>
                                    <a class="admin-link" href="<?= e($company['website']) ?>" target="_blank" rel="noopener noopener"><?= e($company['website']) ?></a>
                                <?php else: ?>
                                    —
                                <?php endif; ?>
                            </td>
                            <td class="admin-actions">
                                <a class="admin-action" href="<?= e(url_path('/admin/companies?edit=' . $company['id'] . '#company-form')) ?>">Editar</a>
                                <a class="admin-action" href="<?= e(url_path('/empresa/' . $company['slug'])) ?>" target="_blank" rel="noopener">Ver página</a>
                                <form method="post" action="<?= e(url_path('/admin/companies/' . $company['id'] . '/delete')) ?>" class="admin-inline-form" onsubmit="return confirm('Excluir esta empresa?');">
                                    <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
                                    <button class="admin-action admin-action-btn admin-action-danger" type="submit">Excluir</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>

<section class="admin-card<?= $showForm ? ' admin-card-highlight' : '' ?>" id="company-form">
    <div class="admin-card-head">
        <h2><?= !empty($editCompany) ? 'Editar empresa' : 'Nova empresa' ?></h2>
    </div>
    <?php if (!$showForm): ?>
        <p class="admin-empty">Clique em <strong>Nova empresa</strong> ou <strong>Editar</strong> para abrir o formulário.</p>
    <?php else: ?>
        <form class="admin-form" method="post" action="<?= e(url_path(!empty($editCompany) ? '/admin/companies/' . $editCompany['id'] . '/edit' : '/admin/companies')) ?>">
            <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
            <div class="admin-form-grid">
                <label class="admin-field admin-field-span-2">
                    <span>Nome</span>
                    <input type="text" name="name" required value="<?= e((string) ($editCompany['name'] ?? '')) ?>">
                </label>
                <label class="admin-field admin-field-span-2">
                    <span>Website <small>(opcional)</small></span>
                    <input type="url" name="website" value="<?= e((string) ($editCompany['website'] ?? '')) ?>" placeholder="https://...">
                </label>
                <label class="admin-field admin-field-span-2">
                    <span>Descrição <small>(opcional)</small></span>
                    <textarea name="description" rows="4" placeholder="Breve descrição da empresa"><?= e((string) ($editCompany['description'] ?? '')) ?></textarea>
                </label>
            </div>
            <div class="admin-form-actions">
                <button class="btn" type="submit"><?= !empty($editCompany) ? 'Salvar alterações' : 'Cadastrar empresa' ?></button>
                <?php if (!empty($editCompany)): ?>
                    <a class="btn btn-outline" href="<?= e(url_path('/admin/companies')) ?>">Cancelar</a>
                <?php endif; ?>
            </div>
        </form>
    <?php endif; ?>
</section>
