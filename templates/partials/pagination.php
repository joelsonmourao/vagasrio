<?php if (($pagination['totalPages'] ?? 1) > 1): ?>
    <nav class="pagination" aria-label="Paginacao">
        <?php
        $base = strtok($_SERVER['REQUEST_URI'], '?');
        $query = $_GET;
        $current = (int) ($pagination['page'] ?? 1);
        $totalPages = (int) ($pagination['totalPages'] ?? 1);
        if ($current > 1):
            $query['page'] = $current - 1;
            ?>
            <a class="pager-nav" href="<?= e($base . '?' . http_build_query($query)) ?>">Anterior</a>
        <?php endif;
        for ($i = 1; $i <= $pagination['totalPages']; $i++):
            if ($i < $current - 2 || $i > $current + 2) {
                continue;
            }
            $query['page'] = $i;
            $url = $base . '?' . http_build_query($query);
            ?>
            <a class="<?= $i === $pagination['page'] ? 'active' : '' ?>" href="<?= e($url) ?>"><?= $i ?></a>
        <?php endfor; ?>
        <?php
        if ($current < $totalPages):
            $query['page'] = $current + 1;
            ?>
            <a class="pager-nav" href="<?= e($base . '?' . http_build_query($query)) ?>">Próxima</a>
        <?php endif; ?>
    </nav>
<?php endif; ?>
