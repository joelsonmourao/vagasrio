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
    <section class="admin-card admin-card-highlight">
        <div class="admin-card-head">
            <h2>Baixar planilha modelo</h2>
        </div>
        <p class="admin-hint">Modelo com <strong>apenas as colunas obrigatórias</strong> para divulgação e preenchimento:</p>
        <pre class="admin-code">title, company, city, state, description, applyUrl</pre>
        <div class="admin-form-actions">
            <a class="btn" href="<?= e(url_path('/admin/import/modelo.csv')) ?>">Baixar CSV (obrigatórias)</a>
            <a class="btn btn-outline" href="<?= e(url_path('/admin/import/modelo.xlsx')) ?>">Baixar XLSX (obrigatórias)</a>
        </div>
        <p class="admin-hint">O <code>applyUrl</code> aceita link (<code>https://...</code>) ou e-mail (<code>rh@empresa.com.br</code>). Use <strong>state = RJ</strong>.</p>
    </section>

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
            <p>A descrição deve ser um <strong>único bloco</strong> (<code>description</code>). Campos legados como <code>requirements</code>, <code>activities</code>, <code>benefits</code> e <code>additionalInfo</code> não fazem parte do modelo oficial; se vierem na planilha, podem ser mesclados em <code>description</code> automaticamente.</p>
            <p>O campo <code>applyUrl</code> pode receber um <strong>link de candidatura</strong> (<code>https://...</code>) ou um <strong>e-mail</strong> da empresa (<code>rh@empresa.com.br</code>).</p>
            <p>Somente vagas com <strong>state = RJ</strong> são importadas. Cidades permitidas: <?= e(implode(', ', allowed_rj_cities())) ?>.</p>
        </div>
        <div class="admin-form-actions">
            <a class="btn btn-sm btn-outline" href="<?= e(url_path('/admin/import/template.csv')) ?>">Modelo completo CSV</a>
            <a class="btn btn-sm btn-outline" href="<?= e(url_path('/admin/import/template.xlsx')) ?>">Modelo completo XLSX</a>
        </div>
    </section>
</div>

<section class="admin-card">
    <div class="admin-card-head">
        <h2>Exemplo de planilha</h2>
    </div>
    <p class="admin-hint">Cabeçalho:</p>
    <pre class="admin-code">title, company, city, state, description, applyUrl, category, salary, employmentType, publishedAt, validThrough</pre>
    <p class="admin-hint"><strong>Exemplo 1</strong> (datas simples — viram 00:00:00 e 23:59:59 no fuso -03:00):</p>
    <pre class="admin-code">Assistente Administrativo, Grupo Horizonte, Rio de Janeiro, RJ, &lt;p&gt;Apoio às rotinas administrativas.&lt;/p&gt;, https://empresa.com/vaga, Administrativo, 1600, FULL_TIME, 2026-06-01, 2026-07-01</pre>
    <p class="admin-hint"><strong>Exemplo 2</strong> (ISO 8601 completo preservado):</p>
    <pre class="admin-code">Auxiliar de Logística, Logística Rio, Duque de Caxias, RJ, &lt;p&gt;Apoio à separação e movimentação de mercadorias.&lt;/p&gt;, rh@empresa.com.br, Logística, 1800, FULL_TIME, 2026-06-01T08:00:00-03:00, 2026-07-01T23:59:59-03:00</pre>
    <div class="admin-note">
        <p><strong>Datas:</strong> se <code>publishedAt</code> vier vazio, usa a data atual (RJ). Se <code>validThrough</code> vier vazio, gera automaticamente 30 dias após a publicação, às 23:59:59 (-03:00).</p>
    </div>
</section>

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
