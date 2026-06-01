<?php $showForm = !empty($showForm); ?>
<section class="admin-page-head">
    <div>
        <p class="admin-kicker">Cadastros</p>
        <h1>Categorias</h1>
        <p class="admin-lead">Organize vagas por área profissional. Use acentuação correta, como Logística.</p>
    </div>
    <div class="admin-page-actions">
        <a class="btn btn-sm" href="<?= e(base_url('/admin/categories?new=1#category-form')) ?>">Nova categoria</a>
    </div>
</section>

<?php require ROOT_PATH . '/templates/partials/admin_flash.php'; ?>

<section class="admin-card">
    <div class="admin-card-head">
        <h2>Categorias cadastradas</h2>
        <span class="admin-meta"><?= count($categories) ?> categoria(s)</span>
    </div>
    <?php if (empty($categories)): ?>
        <p class="admin-empty">Nenhuma categoria cadastrada.</p>
    <?php else: ?>
        <div class="admin-table-wrap">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Nome</th>
                        <th>Slug</th>
                        <th>Vagas</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($categories as $category): ?>
                        <tr>
                            <td><strong><?= e($category['name']) ?></strong></td>
                            <td><code><?= e($category['slug']) ?></code></td>
                            <td><?= (int) ($category['jobs_count'] ?? 0) ?></td>
                            <td class="admin-actions">
                                <a class="admin-action" href="<?= e(base_url('/admin/categories?edit=' . $category['id'] . '#category-form')) ?>">Editar</a>
                                <a class="admin-action" href="<?= e(base_url('/categoria/' . $category['slug'])) ?>" target="_blank" rel="noopener">Ver página</a>
                                <form method="post" action="<?= e(base_url('/admin/categories/' . $category['id'] . '/delete')) ?>" class="admin-inline-form" onsubmit="return confirm('Excluir esta categoria?');">
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

<section class="admin-card<?= $showForm ? ' admin-card-highlight' : '' ?>" id="category-form">
    <div class="admin-card-head">
        <h2><?= !empty($editCategory) ? 'Editar categoria' : 'Nova categoria' ?></h2>
    </div>
    <?php if (!$showForm): ?>
        <p class="admin-empty">Clique em <strong>Nova categoria</strong> ou <strong>Editar</strong> para abrir o formulário.</p>
    <?php else: ?>
        <form class="admin-form" method="post" action="<?= e(base_url(!empty($editCategory) ? '/admin/categories/' . $editCategory['id'] . '/edit' : '/admin/categories')) ?>">
            <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
            <div class="admin-form-grid">
                <label class="admin-field admin-field-span-2">
                    <span>Nome da categoria</span>
                    <input type="text" name="name" required value="<?= e((string) ($editCategory['name'] ?? '')) ?>" placeholder="Ex: Logística, Administrativo, Comercial">
                </label>
            </div>
            <div class="admin-form-actions">
                <button class="btn" type="submit"><?= !empty($editCategory) ? 'Salvar alterações' : 'Cadastrar categoria' ?></button>
                <?php if (!empty($editCategory)): ?>
                    <a class="btn btn-outline" href="<?= e(base_url('/admin/categories')) ?>">Cancelar</a>
                <?php endif; ?>
            </div>
        </form>
    <?php endif; ?>
</section>
