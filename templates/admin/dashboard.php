<section class="admin-page-head">
    <div>
        <p class="admin-kicker">Visão geral</p>
        <h1>Dashboard</h1>
        <p class="admin-lead">Acompanhe vagas, empresas e importações do <?= e(config('site.name')) ?>.</p>
    </div>
    <div class="admin-page-actions">
        <a class="btn btn-sm" href="<?= e(url_path('/admin/jobs?new=1')) ?>">Nova vaga</a>
        <a class="btn btn-sm btn-outline" href="<?= e(url_path('/admin/import')) ?>">Importar planilha</a>
    </div>
</section>

<div class="admin-metrics">
    <article class="admin-metric">
        <span class="admin-metric-label">Total de vagas</span>
        <strong><?= (int) $stats['total'] ?></strong>
    </article>
    <article class="admin-metric admin-metric-success">
        <span class="admin-metric-label">Vagas ativas</span>
        <strong><?= (int) $stats['active'] ?></strong>
    </article>
    <article class="admin-metric admin-metric-muted">
        <span class="admin-metric-label">Vagas inativas</span>
        <strong><?= (int) $stats['inactive'] ?></strong>
    </article>
    <article class="admin-metric admin-metric-accent">
        <span class="admin-metric-label">Publicadas hoje</span>
        <strong><?= (int) $stats['today'] ?></strong>
    </article>
    <article class="admin-metric">
        <span class="admin-metric-label">Total de empresas</span>
        <strong><?= (int) $stats['companies_total'] ?></strong>
    </article>
    <article class="admin-metric">
        <span class="admin-metric-label">Cidades com vagas</span>
        <strong><?= (int) $stats['cities_with_jobs'] ?></strong>
    </article>
</div>

<div class="admin-grid-2">
    <section class="admin-card">
        <div class="admin-card-head">
            <h2>Vagas por cidade</h2>
        </div>
        <?php if (empty($stats['by_city'])): ?>
            <p class="admin-empty">Nenhuma vaga cadastrada ainda.</p>
        <?php else: ?>
            <div class="admin-bar-list">
                <?php $maxQty = max(array_column($stats['by_city'], 'qty')); ?>
                <?php foreach ($stats['by_city'] as $row): ?>
                    <div class="admin-bar-item">
                        <div class="admin-bar-meta">
                            <span><?= e($row['name']) ?></span>
                            <strong><?= (int) $row['qty'] ?></strong>
                        </div>
                        <div class="admin-bar-track">
                            <span style="width:<?= $maxQty > 0 ? round(((int) $row['qty'] / $maxQty) * 100) : 0 ?>%"></span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>

    <section class="admin-card">
        <div class="admin-card-head">
            <h2>Últimas importações</h2>
            <a class="admin-link" href="<?= e(url_path('/admin/import')) ?>">Ver importação</a>
        </div>
        <?php if (empty($stats['recent_imports'])): ?>
            <p class="admin-empty">Nenhuma importação registrada. Envie um arquivo CSV ou XLSX para começar.</p>
        <?php else: ?>
            <div class="admin-table-wrap">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Arquivo</th>
                            <th>Total</th>
                            <th>OK</th>
                            <th>Ignoradas</th>
                            <th>Erros</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($stats['recent_imports'] as $row): ?>
                            <tr>
                                <td><?= e($row['filename']) ?></td>
                                <td><?= (int) $row['total_rows'] ?></td>
                                <td><?= (int) $row['imported_rows'] ?></td>
                                <td><?= (int) $row['ignored_rows'] ?></td>
                                <td><?= (int) $row['error_rows'] ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>
</div>

<section class="admin-card">
    <div class="admin-card-head">
        <h2>Últimas vagas cadastradas</h2>
        <a class="admin-link" href="<?= e(url_path('/admin/jobs')) ?>">Gerenciar vagas</a>
    </div>
    <?php if (empty($stats['recent_jobs'])): ?>
        <p class="admin-empty">Nenhuma vaga cadastrada.</p>
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
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($stats['recent_jobs'] as $row): ?>
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
                                <a class="admin-action" href="<?= e(url_path('/admin/jobs?edit=' . $row['id'])) ?>">Editar</a>
                                <a class="admin-action" href="<?= e(url_path('/vagas/' . $row['slug'])) ?>" target="_blank" rel="noopener">Ver</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>
