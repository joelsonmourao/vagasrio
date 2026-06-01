<section class="admin-page-head">
    <div>
        <p class="admin-kicker">Dados em lote</p>
        <h1>Importação de planilha</h1>
        <p class="admin-lead">Envie CSV ou XLSX com descrição única por vaga. Apenas vagas do RJ são importadas.</p>
    </div>
</section>

<?php require ROOT_PATH . '/templates/partials/admin_flash.php'; ?>

<?php if (!empty($importSummary)): ?>
    <section class="admin-card admin-card-highlight">
        <div class="admin-card-head">
            <h2>Resumo da última importação</h2>
        </div>
        <div class="admin-summary-grid">
            <article class="admin-summary-item">
                <span>Linhas lidas</span>
                <strong><?= (int) $importSummary['total_rows'] ?></strong>
            </article>
            <article class="admin-summary-item admin-summary-success">
                <span>Importadas com sucesso</span>
                <strong><?= (int) $importSummary['imported_rows'] ?></strong>
            </article>
            <article class="admin-summary-item">
                <span>Ignoradas</span>
                <strong><?= (int) $importSummary['ignored_rows'] ?></strong>
            </article>
            <article class="admin-summary-item admin-summary-error">
                <span>Com erro</span>
                <strong><?= (int) $importSummary['error_rows'] ?></strong>
            </article>
        </div>

        <?php if (!empty($importSummary['city_warnings'])): ?>
            <div class="admin-note admin-note-warning">
                <strong>Cidades fora do RJ ignoradas</strong>
                <ul>
                    <?php foreach ($importSummary['city_warnings'] as $warning): ?>
                        <li><?= e($warning) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <?php if (!empty($importSummaryErrors)): ?>
            <div class="admin-note admin-note-error">
                <strong>Motivos dos erros e linhas ignoradas</strong>
                <div class="admin-table-wrap">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>Linha</th>
                                <th>Motivo</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($importSummaryErrors as $error): ?>
                                <tr>
                                    <td><?= (int) $error['row_number'] ?></td>
                                    <td><?= e($error['reason']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>
    </section>
<?php endif; ?>

<div class="admin-grid-2">
    <section class="admin-card">
        <div class="admin-card-head">
            <h2>Enviar planilha</h2>
        </div>
        <form method="post" enctype="multipart/form-data" class="admin-form">
            <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
            <label class="admin-field">
                <span>Arquivo (.csv ou .xlsx)</span>
                <input type="file" name="sheet" accept=".csv,.xlsx,text/csv,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet" required>
            </label>
            <p class="admin-hint">Tamanho máximo: <?= (int) config('jobs.import_max_mb', 10) ?>MB.</p>
            <div class="admin-form-actions">
                <button class="btn" type="submit">Importar vagas</button>
            </div>
        </form>
    </section>

    <section class="admin-card">
        <div class="admin-card-head">
            <h2>Colunas da planilha</h2>
        </div>
        <div class="admin-info-block">
            <h3>Obrigatórias</h3>
            <ul class="admin-tag-list">
                <li><code>title</code></li>
                <li><code>company</code></li>
                <li><code>city</code></li>
                <li><code>state</code></li>
                <li><code>description</code></li>
                <li><code>applyUrl</code></li>
            </ul>
        </div>
        <div class="admin-info-block">
            <h3>Opcionais</h3>
            <ul class="admin-tag-list">
                <li><code>category</code></li>
                <li><code>salary</code></li>
                <li><code>employmentType</code></li>
                <li><code>publishedAt</code></li>
                <li><code>validThrough</code></li>
            </ul>
        </div>
        <div class="admin-note">
            <p>A descrição deve ser um único bloco. Campos antigos como <code>requirements</code>, <code>activities</code>, <code>benefits</code> e <code>additionalInfo</code> são ignorados.</p>
            <p>Somente vagas com <strong>state = RJ</strong> são importadas. Cidades permitidas: <?= e(implode(', ', allowed_rj_cities())) ?>.</p>
        </div>
    </section>
</div>

<section class="admin-card">
    <div class="admin-card-head">
        <h2>Histórico recente</h2>
    </div>
    <?php if (empty($stats['recent_imports'])): ?>
        <p class="admin-empty">Nenhuma importação registrada ainda. Envie sua primeira planilha acima.</p>
    <?php else: ?>
        <div class="admin-table-wrap">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Arquivo</th>
                        <th>Data</th>
                        <th>Total</th>
                        <th>Importadas</th>
                        <th>Ignoradas</th>
                        <th>Erros</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($stats['recent_imports'] as $row): ?>
                        <tr>
                            <td><?= e($row['filename']) ?></td>
                            <td><?= e(date('d/m/Y H:i', strtotime($row['created_at']))) ?></td>
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
