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
                    <li><a href="/vagas">Vagas</a></li>
                    <li><a href="/cidades">Cidades</a></li>
                    <li><a href="/empresas">Empresas</a></li>
                    <li><a href="/categorias">Categorias</a></li>
                    <li><a href="/blog">Blog</a></li>
                </ul>
            </section>
            <section class="footer-col">
                <h4>Cidades do RJ</h4>
                <ul>
                    <?php if (empty($citiesMenu)): ?>
                        <li><a href="/cidades">Ver cidades</a></li>
                    <?php else: ?>
                        <?php foreach ($citiesMenu as $city): ?>
                            <li><a href="<?= e(url_path('/cidade/' . $city['slug'])) ?>"><?= e($city['name']) ?></a></li>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </ul>
            </section>
            <section class="footer-col">
                <h4>Institucional</h4>
                <ul>
                    <li><a href="/sobre">Sobre</a></li>
                    <li><a href="/contato">Contato</a></li>
                    <li><a href="/politica-de-privacidade">Privacidade</a></li>
                    <li><a href="/politica-de-cookies">Cookies</a></li>
                    <li><a href="/termos-de-uso">Termos</a></li>
                </ul>
            </section>
        </div>
        <div class="footer-note">
            <p>O Vagas RJ divulga oportunidades de emprego no estado do Rio de Janeiro. O portal não cobra taxa de candidatura e não garante contratação. Confira sempre as informações no site oficial da empresa ou no link de candidatura.</p>
        </div>
    </div>
</footer>
