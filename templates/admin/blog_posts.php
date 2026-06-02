<?php $showForm = !empty($showForm); ?>
<section class="admin-page-head">
    <div>
        <p class="admin-kicker">Blog</p>
        <h1>Artigos do blog</h1>
        <p class="admin-lead">Gerencie conteúdo original para candidatos do Rio de Janeiro.</p>
    </div>
    <div class="admin-page-actions">
        <a class="btn btn-sm" href="<?= e(url_path('/admin/blog/posts?new=1#blog-post-form')) ?>">Novo artigo</a>
        <a class="btn btn-sm btn-outline" href="<?= e(url_path('/admin/blog/categories')) ?>">Categorias</a>
    </div>
</section>

<?php require ROOT_PATH . '/templates/partials/admin_flash.php'; ?>

<section class="admin-card">
    <div class="admin-card-head">
        <h2>Seed inicial</h2>
    </div>
    <p class="admin-lead">Carrega 11 categorias e 121 artigos originais (se ainda não existirem).</p>
    <form method="post" action="<?= e(url_path('/admin/blog/seed')) ?>" class="admin-inline-form" style="gap:12px;flex-wrap:wrap">
        <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
        <button class="btn btn-sm" type="submit">Executar seed</button>
        <label class="admin-field" style="flex-direction:row;align-items:center;gap:8px;margin:0">
            <input type="checkbox" name="force" value="1">
            <span>Forçar recriação (apaga artigos atuais)</span>
        </label>
    </form>
</section>

<section class="admin-card">
    <div class="admin-card-head">
        <h2>Artigos</h2>
        <form method="get" class="admin-inline-form">
            <select name="category" onchange="this.form.submit()">
                <option value="">Todas as categorias</option>
                <?php foreach ($blogCategories as $cat): ?>
                    <option value="<?= e($cat['slug']) ?>" <?= ($categoryFilter ?? '') === $cat['slug'] ? 'selected' : '' ?>><?= e($cat['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </form>
    </div>
    <?php if (empty($posts)): ?>
        <p class="admin-empty">Nenhum artigo. Execute o seed ou cadastre manualmente.</p>
    <?php else: ?>
        <div class="admin-table-wrap">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Título</th>
                        <th>Categoria</th>
                        <th>Publicação</th>
                        <th>Status</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($posts as $post): ?>
                        <tr>
                            <td><strong><?= e($post['title']) ?></strong><br><code><?= e($post['slug']) ?></code></td>
                            <td><?= e($post['category_name'] ?? '') ?></td>
                            <td><?= e(format_date_br($post['published_at'])) ?></td>
                            <td><?= !empty($post['is_active']) ? 'Ativo' : 'Inativo' ?></td>
                            <td class="admin-actions">
                                <a class="admin-action" href="<?= e(url_path('/admin/blog/posts?edit=' . $post['id'] . '#blog-post-form')) ?>">Editar</a>
                                <a class="admin-action" href="<?= e(url_path('/blog/' . $post['slug'])) ?>" target="_blank" rel="noopener">Ver</a>
                                <form method="post" action="<?= e(url_path('/admin/blog/posts/' . $post['id'] . '/toggle')) ?>" class="admin-inline-form">
                                    <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
                                    <button class="admin-action admin-action-btn" type="submit"><?= !empty($post['is_active']) ? 'Desativar' : 'Ativar' ?></button>
                                </form>
                                <form method="post" action="<?= e(url_path('/admin/blog/posts/' . $post['id'] . '/delete')) ?>" class="admin-inline-form" onsubmit="return confirm('Excluir artigo?');">
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

<section class="admin-card<?= $showForm ? ' admin-card-highlight' : '' ?>" id="blog-post-form">
    <div class="admin-card-head"><h2><?= !empty($editPost) ? 'Editar artigo' : 'Novo artigo' ?></h2></div>
    <?php if (!$showForm): ?>
        <p class="admin-empty">Clique em <strong>Novo artigo</strong> ou <strong>Editar</strong>.</p>
    <?php else: ?>
        <form class="admin-form" method="post" action="<?= e(url_path(!empty($editPost) ? '/admin/blog/posts/' . $editPost['id'] . '/edit' : '/admin/blog/posts/new')) ?>">
            <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
            <div class="admin-form-grid">
                <label class="admin-field admin-field-span-2"><span>Título</span><input type="text" name="title" required value="<?= e((string) ($editPost['title'] ?? '')) ?>"></label>
                <label class="admin-field"><span>Slug (opcional)</span><input type="text" name="slug" value="<?= e((string) ($editPost['slug'] ?? '')) ?>"></label>
                <label class="admin-field"><span>Categoria</span>
                    <select name="category_id" required>
                        <option value="">Selecione</option>
                        <?php foreach ($blogCategories as $cat): ?>
                            <option value="<?= (int) $cat['id'] ?>" <?= (int) ($editPost['category_id'] ?? 0) === (int) $cat['id'] ? 'selected' : '' ?>><?= e($cat['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label class="admin-field admin-field-span-2"><span>Resumo (excerpt)</span><textarea name="excerpt" rows="2"><?= e((string) ($editPost['excerpt'] ?? '')) ?></textarea></label>
                <label class="admin-field admin-field-span-2"><span>Conteúdo HTML</span><textarea name="content" rows="14" required><?= e((string) ($editPost['content'] ?? '')) ?></textarea></label>
                <label class="admin-field"><span>SEO title</span><input type="text" name="seo_title" value="<?= e((string) ($editPost['seo_title'] ?? '')) ?>"></label>
                <label class="admin-field"><span>SEO description</span><input type="text" name="seo_description" value="<?= e((string) ($editPost['seo_description'] ?? '')) ?>"></label>
                <label class="admin-field"><span>Publicado em</span><input type="date" name="published_at" value="<?= e(substr((string) ($editPost['published_at'] ?? date('c')), 0, 10)) ?>"></label>
                <label class="admin-field"><span><input type="checkbox" name="is_active" value="1" <?= empty($editPost) || !empty($editPost['is_active']) ? 'checked' : '' ?>> Ativo</span></label>
            </div>
            <div class="admin-form-actions">
                <button class="btn" type="submit"><?= !empty($editPost) ? 'Salvar' : 'Publicar' ?></button>
                <?php if (!empty($editPost)): ?><a class="btn btn-outline" href="<?= e(url_path('/admin/blog/posts')) ?>">Cancelar</a><?php endif; ?>
            </div>
        </form>
    <?php endif; ?>
</section>
