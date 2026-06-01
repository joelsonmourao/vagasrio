<footer class="site-footer">
    <div class="container footer-inner">
        <div class="footer-grid">
            <section class="footer-brand">
                <h3>Vagas RJ</h3>
                <p>Portal de oportunidades no Rio de Janeiro. Busque vagas por cidade, empresa e área profissional.</p>
            </section>
            <section class="footer-col">
                <h4>Portal</h4>
                <ul>
                    <li><a href="<?= e(base_url('/vagas')) ?>">Vagas</a></li>
                    <li><a href="<?= e(base_url('/cidades')) ?>">Cidades</a></li>
                    <li><a href="<?= e(base_url('/empresas')) ?>">Empresas</a></li>
                    <li><a href="<?= e(base_url('/categorias')) ?>">Categorias</a></li>
                    <li><a href="<?= e(base_url('/blog')) ?>">Blog</a></li>
                </ul>
            </section>
            <section class="footer-col">
                <h4>Cidades do RJ</h4>
                <ul>
                    <?php if (empty($citiesMenu)): ?>
                        <li><a href="<?= e(base_url('/cidades')) ?>">Ver cidades</a></li>
                    <?php else: ?>
                        <?php foreach ($citiesMenu as $city): ?>
                            <li><a href="<?= e(base_url('/cidade/' . $city['slug'])) ?>"><?= e($city['name']) ?></a></li>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </ul>
            </section>
            <section class="footer-col">
                <h4>Institucional</h4>
                <ul>
                    <li><a href="<?= e(base_url('/sobre')) ?>">Sobre</a></li>
                    <li><a href="<?= e(base_url('/contato')) ?>">Contato</a></li>
                    <li><a href="<?= e(base_url('/politica-de-privacidade')) ?>">Privacidade</a></li>
                    <li><a href="<?= e(base_url('/politica-de-cookies')) ?>">Cookies</a></li>
                    <li><a href="<?= e(base_url('/termos-de-uso')) ?>">Termos</a></li>
                </ul>
            </section>
        </div>
        <div class="footer-note">
            <p>O Vagas RJ divulga oportunidades de emprego no estado do Rio de Janeiro. O portal não cobra taxa de candidatura e não garante contratação. Confira sempre as informações no site oficial da empresa ou no link de candidatura.</p>
        </div>
    </div>
</footer>
