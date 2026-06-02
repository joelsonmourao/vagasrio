<?php if (($pagination['totalPages'] ?? 1) <= 1) return; ?>
<nav class="pagination" aria-label="Paginação">
    <?php
    $current = (int) ($pagination['page'] ?? 1);
    $totalPages = (int) ($pagination['totalPages'] ?? 1);
    $basePath = (string) ($pagination['basePath'] ?? strtok($_SERVER['REQUEST_URI'] ?? '/', '?'));
    $query = $pagination['query'] ?? $_GET;
    if (!is_array($query)) {
        $query = [];
    }
    unset($query['page']);
    $useQuery = !empty($pagination['useQuery']);

    $buildUrl = static function (int $page) use ($basePath, $query, $useQuery): string {
        if ($useQuery) {
            $params = $query;
            if ($page > 1) {
                $params['page'] = $page;
            }

            $url = url_path(strtok($basePath, '?'));

            return $params !== [] ? $url . '?' . http_build_query($params) : $url;
        }

        return pagination_build_url($basePath, $page, $query);
    };
    ?>
    <?php if ($current > 1): ?>
        <a class="pager-nav" href="<?= e($buildUrl($current - 1)) ?>" rel="prev">Anterior</a>
    <?php endif; ?>

    <?php
    $start = max(1, $current - 2);
    $end = min($totalPages, $current + 2);
    if ($start > 1): ?>
        <a href="<?= e($buildUrl(1)) ?>">1</a>
        <?php if ($start > 2): ?><span class="pager-ellipsis" aria-hidden="true">…</span><?php endif; ?>
    <?php endif; ?>

    <?php for ($i = $start; $i <= $end; $i++): ?>
        <?php if ($i === $current): ?>
            <span class="active" aria-current="page"><?= $i ?></span>
        <?php else: ?>
            <a href="<?= e($buildUrl($i)) ?>"><?= $i ?></a>
        <?php endif; ?>
    <?php endfor; ?>

    <?php if ($end < $totalPages): ?>
        <?php if ($end < $totalPages - 1): ?><span class="pager-ellipsis" aria-hidden="true">…</span><?php endif; ?>
        <a href="<?= e($buildUrl($totalPages)) ?>"><?= $totalPages ?></a>
    <?php endif; ?>

    <?php if ($current < $totalPages): ?>
        <a class="pager-nav" href="<?= e($buildUrl($current + 1)) ?>" rel="next">Próxima</a>
    <?php endif; ?>

    <p class="pagination-meta">Página <?= $current ?> de <?= $totalPages ?></p>
</nav>
