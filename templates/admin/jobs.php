<?php
$filters = [
    'q' => (string) ($_GET['q'] ?? ''),
    'city' => (string) ($_GET['city'] ?? ''),
    'company' => (string) ($_GET['company'] ?? ''),
    'category' => (string) ($_GET['category'] ?? ''),
    'status' => (string) ($_GET['status'] ?? ''),
];
$showForm = !empty($showForm);
?>
<section class="admin-page-head">
    <div>
        <p class="admin-kicker">Conteúdo</p>
        <h1>Gerenciar vagas</h1>
        <p class="admin-lead">Cadastre, edite e publique vagas do Rio de Janeiro com descrição única.</p>
    </div>
    <div class="admin-page-actions">
        <a class="btn btn-sm" href="<?= e(url_path('/admin/jobs?new=1#job-form')) ?>">Nova vaga</a>
    </div>
</section>

<?php require ROOT_PATH . '/templates/partials/admin_flash.php'; ?>

<section class="admin-card">
    <div class="admin-card-head">
        <h2>Filtros</h2>
        <span class="admin-meta"><?= (int) $jobsData['total'] ?> vaga(s) encontrada(s)</span>
    </div>
    <form method="get" class="admin-filters">
        <label>
            <span>Buscar por título</span>
            <input type="text" name="q" value="<?= e($filters['q']) ?>" placeholder="Ex: assistente administrativo">
        </label>
        <label>
            <span>Cidade</span>
            <select name="city">
                <option value="">Todas</option>
                <?php foreach ($cities as $city): ?>
                    <option value="<?= e($city['slug']) ?>" <?= $filters['city'] === $city['slug'] ? 'selected' : '' ?>><?= e($city['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>
            <span>Empresa</span>
            <select name="company">
                <option value="">Todas</option>
                <?php foreach ($companies as $company): ?>
                    <option value="<?= e($company['slug']) ?>" <?= $filters['company'] === $company['slug'] ? 'selected' : '' ?>><?= e($company['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>
            <span>Categoria</span>
            <select name="category">
                <option value="">Todas</option>
                <?php foreach ($categories as $category): ?>
                    <option value="<?= e($category['slug']) ?>" <?= $filters['category'] === $category['slug'] ? 'selected' : '' ?>><?= e($category['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>
            <span>Status</span>
            <select name="status">
                <option value="">Todos</option>
                <option value="active" <?= $filters['status'] === 'active' ? 'selected' : '' ?>>Ativas</option>
                <option value="inactive" <?= $filters['status'] === 'inactive' ? 'selected' : '' ?>>Inativas</option>
            </select>
        </label>
        <div class="admin-filter-actions">
            <button class="btn btn-sm" type="submit">Filtrar</button>
            <a class="btn btn-sm btn-outline" href="<?= e(url_path('/admin/jobs')) ?>">Limpar</a>
        </div>
    </form>
</section>

<section class="admin-card">
    <div class="admin-card-head">
        <h2>Lista de vagas</h2>
    </div>
    <?php if (empty($jobsData['jobs'])): ?>
        <p class="admin-empty">Nenhuma vaga encontrada com os filtros atuais.</p>
    <?php else: ?>
        <div class="admin-table-wrap">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Título</th>
                        <th>Empresa</th>
                        <th>Cidade</th>
                        <th>Categoria</th>
                        <th>Status</th>
                        <th>Publicação</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($jobsData['jobs'] as $row): ?>
                        <tr>
                            <td><strong><?= e($row['title']) ?></strong></td>
                            <td><?= e($row['company_name']) ?></td>
                            <td><?= e($row['city_name']) ?></td>
                            <td><?= e((string) ($row['category_name'] ?? '—')) ?></td>
                            <td>
                                <span class="admin-badge <?= $row['is_active'] ? 'admin-badge-success' : 'admin-badge-muted' ?>">
                                    <?= $row['is_active'] ? 'Ativa' : 'Inativa' ?>
                                </span>
                            </td>
                            <td><?= e(format_date_br($row['published_at'])) ?></td>
                            <td class="admin-actions">
                                <a class="admin-action" href="<?= e(url_path('/admin/jobs?edit=' . $row['id'] . '#job-form')) ?>">Editar</a>
                                <form method="post" action="<?= e(url_path('/admin/jobs/' . $row['id'] . '/toggle')) ?>" class="admin-inline-form">
                                    <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
                                    <button class="admin-action admin-action-btn" type="submit"><?= $row['is_active'] ? 'Desativar' : 'Ativar' ?></button>
                                </form>
                                <a class="admin-action" href="<?= e(url_path('/vagas/' . $row['slug'])) ?>" target="_blank" rel="noopener">Ver</a>
                                <form method="post" action="<?= e(url_path('/admin/jobs/' . $row['id'] . '/delete')) ?>" class="admin-inline-form" onsubmit="return confirm('Excluir esta vaga?');">
                                    <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
                                    <button class="admin-action admin-action-btn admin-action-danger" type="submit">Excluir</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php $pagination = $jobsData; require ROOT_PATH . '/templates/partials/pagination.php'; ?>
    <?php endif; ?>
</section>

<section class="admin-card<?= $showForm ? ' admin-card-highlight' : '' ?>" id="job-form">
    <div class="admin-card-head">
        <h2><?= $editJob ? 'Editar vaga' : 'Nova vaga' ?></h2>
        <?php if (!$showForm): ?>
            <a class="admin-link" href="<?= e(url_path('/admin/jobs?new=1#job-form')) ?>">Abrir formulário</a>
        <?php elseif (!$editJob): ?>
            <a class="admin-link" href="<?= e(url_path('/admin/jobs')) ?>">Fechar</a>
        <?php endif; ?>
    </div>

    <?php if (!$showForm): ?>
        <p class="admin-empty">Use o botão <strong>Nova vaga</strong> para cadastrar ou selecione <strong>Editar</strong> em uma vaga da lista.</p>
    <?php else: ?>
        <form class="admin-form" method="post" action="<?= e(url_path($editJob ? '/admin/jobs/' . $editJob['id'] . '/edit' : '/admin/jobs/new')) ?>">
            <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
            <div class="admin-form-grid">
                <label class="admin-field admin-field-span-2">
                    <span>Título</span>
                    <input type="text" name="title" required value="<?= e((string) ($editJob['title'] ?? '')) ?>">
                </label>
                <label class="admin-field">
                    <span>Empresa</span>
                    <select name="company_id" required>
                        <option value="">Selecione</option>
                        <?php foreach ($companies as $company): ?>
                            <option value="<?= (int) $company['id'] ?>" <?= ((int) ($editJob['company_id'] ?? 0) === (int) $company['id']) ? 'selected' : '' ?>><?= e($company['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label class="admin-field">
                    <span>Cidade</span>
                    <select name="city_id" required>
                        <option value="">Selecione</option>
                        <?php foreach ($cities as $city): ?>
                            <option value="<?= (int) $city['id'] ?>" <?= ((int) ($editJob['city_id'] ?? 0) === (int) $city['id']) ? 'selected' : '' ?>><?= e($city['name']) ?>/RJ</option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label class="admin-field">
                    <span>Estado</span>
                    <input type="text" value="RJ" readonly>
                </label>
                <label class="admin-field">
                    <span>Categoria <small>(opcional)</small></span>
                    <select name="category_id">
                        <option value="">Sem categoria</option>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?= (int) $category['id'] ?>" <?= ((int) ($editJob['category_id'] ?? 0) === (int) $category['id']) ? 'selected' : '' ?>><?= e($category['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label class="admin-field admin-field-span-2">
                    <span>Descrição única da vaga <small>(HTML simples ou texto)</small></span>
                    <textarea name="description" rows="10" required placeholder="Texto completo da vaga"><?= e((string) ($editJob['description'] ?? '')) ?></textarea>
                </label>
                <label class="admin-field">
                    <span>Link ou e-mail de candidatura</span>
                    <input type="text" name="apply_url" value="<?= e((string) ($editJob['apply_url'] ?? '')) ?>" placeholder="https://... ou email@empresa.com.br">
                </label>
                <label class="admin-field">
                    <span>Salário <small>(opcional)</small></span>
                    <input type="text" name="salary" value="<?= e((string) ($editJob['salary'] ?? '')) ?>" placeholder="Ex: R$ 3.500,00">
                </label>
                <label class="admin-field">
                    <span>Tipo de contratação <small>(opcional)</small></span>
                    <input type="text" name="employment_type" value="<?= e((string) ($editJob['employment_type'] ?? '')) ?>" placeholder="Ex: FULL_TIME, PART_TIME, PJ">
                </label>
                <label class="admin-field">
                    <span>Data de publicação</span>
                    <input type="date" name="published_at" value="<?= e((string) (!empty($editJob['published_at']) ? date('Y-m-d', strtotime($editJob['published_at'])) : date('Y-m-d'))) ?>">
                </label>
                <label class="admin-field">
                    <span>Validade da vaga <small>(opcional)</small></span>
                    <input type="date" name="valid_through" value="<?= e((string) (!empty($editJob['valid_through']) ? date('Y-m-d', strtotime($editJob['valid_through'])) : '')) ?>">
                </label>
                <label class="admin-field admin-field-check">
                    <input type="checkbox" name="is_active" value="1" <?= ((int) ($editJob['is_active'] ?? 1) === 1) ? 'checked' : '' ?>>
                    <span>Vaga ativa</span>
                </label>
            </div>
            <div class="admin-form-actions">
                <button class="btn" type="submit"><?= $editJob ? 'Salvar alterações' : 'Cadastrar vaga' ?></button>
                <?php if ($editJob): ?>
                    <a class="btn btn-outline" href="<?= e(url_path('/admin/jobs')) ?>">Cancelar edição</a>
                <?php endif; ?>
            </div>
        </form>
    <?php endif; ?>
</section>
