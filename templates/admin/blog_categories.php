<?php $showForm = !empty($showForm); ?>
<section class="admin-page-head">
    <div>
        <p class="admin-kicker">Blog</p>
        <h1>Categorias do blog</h1>
        <p class="admin-lead">Organize artigos por tema: currículo, entrevista, mercado de trabalho no RJ etc.</p>
    </div>
    <div class="admin-page-actions">
        <a class="btn btn-sm" href="<?= e(url_path('/admin/blog/categories?new=1#blog-category-form')) ?>">Nova categoria</a>
        <a class="btn btn-sm btn-outline" href="<?= e(url_path('/admin/blog/posts')) ?>">Ver artigos</a>
    </div>
</section>

<?php require ROOT_PATH . '/templates/partials/admin_flash.php'; ?>

<section class="admin-card">
    <div class="admin-card-head">
        <h2>Categorias</h2>
        <span class="admin-meta"><?= count($categories) ?> categoria(s)</span>
    </div>
    <?php if (empty($categories)): ?>
        <p class="admin-empty">Nenhuma categoria. Use o seed inicial em Artigos do blog.</p>
    <?php else: ?>
        <div class="admin-table-wrap">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Nome</th>
                        <th>Slug</th>
                        <th>Artigos</th>
                        <th>Status</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($categories as $category): ?>
                        <tr>
                            <td><strong><?= e($category['name']) ?></strong></td>
                            <td><code><?= e($category['slug']) ?></code></td>
                            <td><?= (int) ($category['posts_count'] ?? 0) ?></td>
                            <td><?= !empty($category['is_active']) ? 'Ativa' : 'Inativa' ?></td>
                            <td class="admin-actions">
                                <a class="admin-action" href="<?= e(url_path('/admin/blog/categories?edit=' . $category['id'] . '#blog-category-form')) ?>">Editar</a>
                                <a class="admin-action" href="<?= e(blog_category_public_path($category['slug'])) ?>" target="_blank" rel="noopener">Ver página</a>
                                <form method="post" action="<?= e(url_path('/admin/blog/categories/' . $category['id'] . '/delete')) ?>" class="admin-inline-form" onsubmit="return confirm('Excluir categoria?');">
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

<section class="admin-card<?= $showForm ? ' admin-card-highlight' : '' ?>" id="blog-category-form">
    <div class="admin-card-head"><h2><?= !empty($editCategory) ? 'Editar categoria' : 'Nova categoria' ?></h2></div>
    <?php if (!$showForm): ?>
        <p class="admin-empty">Clique em <strong>Nova categoria</strong> ou <strong>Editar</strong>.</p>
    <?php else: ?>
        <form class="admin-form" method="post" action="<?= e(url_path(!empty($editCategory) ? '/admin/blog/categories/' . $editCategory['id'] . '/edit' : '/admin/blog/categories')) ?>">
            <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
            <div class="admin-form-grid">
                <label class="admin-field admin-field-span-2"><span>Nome</span><input type="text" name="name" required value="<?= e((string) ($editCategory['name'] ?? '')) ?>"></label>
                <label class="admin-field admin-field-span-2"><span>Descrição</span><textarea name="description" rows="3"><?= e((string) ($editCategory['description'] ?? '')) ?></textarea></label>
                <label class="admin-field"><span><input type="checkbox" name="is_active" value="1" <?= empty($editCategory) || !empty($editCategory['is_active']) ? 'checked' : '' ?>> Ativa</span></label>
            </div>
            <div class="admin-form-actions">
                <button class="btn" type="submit"><?= !empty($editCategory) ? 'Salvar' : 'Cadastrar' ?></button>
                <?php if (!empty($editCategory)): ?><a class="btn btn-outline" href="<?= e(url_path('/admin/blog/categories')) ?>">Cancelar</a><?php endif; ?>
            </div>
        </form>
    <?php endif; ?>
</section>
