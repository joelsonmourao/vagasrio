<?php

declare(strict_types=1);

require dirname(__DIR__) . '/app/bootstrap.php';

use App\Services\PortalService;
use App\Services\SitemapService;

$service = new PortalService(db());
$sitemapService = new SitemapService(db(), $service);
$path = current_path();
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if ($path === '/sitemap.xml') {
    header('Content-Type: application/xml; charset=UTF-8');
    echo SitemapService::renderIndex($sitemapService->indexLocations());
    exit;
}

if (preg_match('#^/sitemap-[a-z0-9\-]+\.xml$#', $path)) {
    $chunk = $sitemapService->chunkByRequestPath($path);
    if ($chunk === null) {
        http_response_code(404);
        exit;
    }
    header('Content-Type: application/xml; charset=UTF-8');
    echo SitemapService::renderUrlset($chunk['urls']);
    exit;
}

if ($path === '/robots.txt') {
    header('Content-Type: text/plain; charset=UTF-8');
    echo "User-agent: *\nAllow: /\nDisallow: /admin\nDisallow: /admin/\nDisallow: /scripts\nSitemap: " . base_url('/sitemap.xml');
    exit;
}

if ($path === '/admin/login' && $method === 'POST') {
    if (!verify_csrf($_POST['_csrf'] ?? null)) {
        $_SESSION['flash_error'] = 'Token inválido.';
        redirect('/admin/login');
    }
    $username = (string) ($_POST['username'] ?? '');
    $password = (string) ($_POST['password'] ?? '');
    if (admin_login_ok($username, $password)) {
        $_SESSION['admin_logged'] = true;
        redirect('/admin');
    }
    $_SESSION['flash_error'] = 'Credenciais invalidas.';
    redirect('/admin/login');
}

if ($path === '/admin/logout') {
    session_destroy();
    redirect('/admin/login');
}

if (str_starts_with($path, '/admin')) {
    if ($path !== '/admin/login') {
        require_admin();
    }

    if ($path === '/admin/login') {
        render('admin/login', [
            'title' => 'Login Admin',
            'pageType' => 'admin',
            'disableAds' => true,
            'flashError' => $_SESSION['flash_error'] ?? null,
        ], 'admin_layout');
        unset($_SESSION['flash_error']);
        exit;
    }

    if ($path === '/admin' || $path === '/admin/dashboard') {
        render('admin/dashboard', [
            'title' => 'Dashboard',
            'stats' => $service->dashboardStats(),
            'pageType' => 'admin',
            'disableAds' => true,
        ], 'admin_layout');
        exit;
    }

    if ($path === '/admin/jobs/new' && $method === 'POST') {
        if (!verify_csrf($_POST['_csrf'] ?? null)) {
            $_SESSION['flash_error'] = 'Token inválido.';
            redirect('/admin/jobs');
        }
        try {
            $service->saveJob($_POST);
            $_SESSION['flash_ok'] = 'Vaga cadastrada com sucesso.';
        } catch (Throwable $e) {
            $_SESSION['flash_error'] = $e->getMessage();
        }
        redirect('/admin/jobs');
    }

    if (preg_match('#^/admin/jobs/(\d+)/edit$#', $path, $matches) && $method === 'POST') {
        if (!verify_csrf($_POST['_csrf'] ?? null)) {
            $_SESSION['flash_error'] = 'Token inválido.';
            redirect('/admin/jobs');
        }
        try {
            $service->saveJob($_POST, (int) $matches[1]);
            $_SESSION['flash_ok'] = 'Vaga atualizada com sucesso.';
        } catch (Throwable $e) {
            $_SESSION['flash_error'] = $e->getMessage();
        }
        redirect('/admin/jobs');
    }

    if ($path === '/admin/jobs/bulk-delete' && $method === 'POST') {
        $redirectParams = array_filter([
            'q' => (string) ($_POST['filter_q'] ?? ''),
            'city' => (string) ($_POST['filter_city'] ?? ''),
            'company' => (string) ($_POST['filter_company'] ?? ''),
            'category' => (string) ($_POST['filter_category'] ?? ''),
            'status' => (string) ($_POST['filter_status'] ?? ''),
            'page' => (string) ($_POST['filter_page'] ?? ''),
        ], static fn ($value) => $value !== '');
        $redirectUrl = '/admin/jobs' . ($redirectParams !== [] ? '?' . http_build_query($redirectParams) : '');

        if (!verify_csrf($_POST['_csrf'] ?? null)) {
            $_SESSION['flash_error'] = 'Token inválido.';
            redirect($redirectUrl);
        }

        try {
            $ids = $_POST['job_ids'] ?? [];
            if (!is_array($ids)) {
                $ids = [];
            }
            $count = $service->deleteJobsByIds($ids);
            $_SESSION['flash_ok'] = $count === 1 ? '1 vaga removida.' : $count . ' vagas removidas.';
        } catch (Throwable $e) {
            $_SESSION['flash_error'] = $e->getMessage();
        }
        redirect($redirectUrl);
    }

    if (preg_match('#^/admin/jobs/(\d+)/delete$#', $path, $matches) && $method === 'POST') {
        if (!verify_csrf($_POST['_csrf'] ?? null)) {
            $_SESSION['flash_error'] = 'Token inválido.';
            redirect('/admin/jobs');
        }
        try {
            $service->deleteJob((int) $matches[1]);
            $_SESSION['flash_ok'] = 'Vaga removida.';
        } catch (Throwable $e) {
            $_SESSION['flash_error'] = $e->getMessage();
        }
        redirect('/admin/jobs');
    }

    if (preg_match('#^/admin/jobs/(\d+)/toggle$#', $path, $matches) && $method === 'POST') {
        if (verify_csrf($_POST['_csrf'] ?? null)) {
            $service->toggleJob((int) $matches[1]);
            $_SESSION['flash_ok'] = 'Status da vaga atualizado.';
        }
        redirect('/admin/jobs');
    }

    if ($path === '/admin/companies' && $method === 'POST' && empty($_POST['_action'])) {
        if (!verify_csrf($_POST['_csrf'] ?? null)) {
            $_SESSION['flash_error'] = 'Token inválido.';
            redirect('/admin/companies');
        }
        try {
            $service->createCompany($_POST);
            $_SESSION['flash_ok'] = 'Empresa cadastrada.';
        } catch (Throwable $e) {
            $_SESSION['flash_error'] = $e->getMessage();
        }
        redirect('/admin/companies');
    }

    if (preg_match('#^/admin/companies/(\d+)/edit$#', $path, $matches) && $method === 'POST') {
        if (!verify_csrf($_POST['_csrf'] ?? null)) {
            $_SESSION['flash_error'] = 'Token inválido.';
            redirect('/admin/companies');
        }
        try {
            $service->updateCompany((int) $matches[1], $_POST);
            $_SESSION['flash_ok'] = 'Empresa atualizada.';
        } catch (Throwable $e) {
            $_SESSION['flash_error'] = $e->getMessage();
        }
        redirect('/admin/companies');
    }

    if (preg_match('#^/admin/companies/(\d+)/delete$#', $path, $matches) && $method === 'POST') {
        if (verify_csrf($_POST['_csrf'] ?? null)) {
            try {
                $service->deleteCompany((int) $matches[1]);
                $_SESSION['flash_ok'] = 'Empresa removida.';
            } catch (Throwable $e) {
                $_SESSION['flash_error'] = $e->getMessage();
            }
        }
        redirect('/admin/companies');
    }

    if ($path === '/admin/categories' && $method === 'POST' && empty($_POST['_action'])) {
        if (!verify_csrf($_POST['_csrf'] ?? null)) {
            $_SESSION['flash_error'] = 'Token inválido.';
            redirect('/admin/categories');
        }
        try {
            $service->createCategory($_POST);
            $_SESSION['flash_ok'] = 'Categoria cadastrada.';
        } catch (Throwable $e) {
            $_SESSION['flash_error'] = $e->getMessage();
        }
        redirect('/admin/categories');
    }

    if (preg_match('#^/admin/categories/(\d+)/edit$#', $path, $matches) && $method === 'POST') {
        if (!verify_csrf($_POST['_csrf'] ?? null)) {
            $_SESSION['flash_error'] = 'Token inválido.';
            redirect('/admin/categories');
        }
        try {
            $service->updateCategory((int) $matches[1], $_POST);
            $_SESSION['flash_ok'] = 'Categoria atualizada.';
        } catch (Throwable $e) {
            $_SESSION['flash_error'] = $e->getMessage();
        }
        redirect('/admin/categories');
    }

    if (preg_match('#^/admin/categories/(\d+)/delete$#', $path, $matches) && $method === 'POST') {
        if (verify_csrf($_POST['_csrf'] ?? null)) {
            try {
                $service->deleteCategory((int) $matches[1]);
                $_SESSION['flash_ok'] = 'Categoria removida.';
            } catch (Throwable $e) {
                $_SESSION['flash_error'] = $e->getMessage();
            }
        }
        redirect('/admin/categories');
    }

    if ($path === '/admin/import/template.csv') {
        output_import_template_csv();
        exit;
    }

    if ($path === '/admin/import/template.xlsx') {
        output_import_template_xlsx();
        exit;
    }

    if ($path === '/admin/import/modelo.csv') {
        output_import_template_required_csv();
        exit;
    }

    if ($path === '/admin/import/modelo.xlsx') {
        output_import_template_required_xlsx();
        exit;
    }

    if ($path === '/admin/import' && $method === 'POST') {
        if (!verify_csrf($_POST['_csrf'] ?? null)) {
            $_SESSION['flash_error'] = 'Token inválido.';
            redirect('/admin/import');
        }
        try {
            if (!isset($_FILES['sheet']) || (int) $_FILES['sheet']['error'] !== 0) {
                throw new RuntimeException('Envie uma planilha .csv ou .xlsx valida.');
            }
            $originalName = (string) ($_FILES['sheet']['name'] ?? '');
            $size = (int) ($_FILES['sheet']['size'] ?? 0);
            if ($size < 1) {
                throw new RuntimeException('Arquivo de importacao vazio.');
            }
            $maxBytes = (int) config('jobs.import_max_mb', 10) * 1024 * 1024;
            if ($size > $maxBytes) {
                throw new RuntimeException('Arquivo excede o limite de ' . (int) config('jobs.import_max_mb', 10) . 'MB.');
            }
            $ext = strtolower((string) pathinfo($originalName, PATHINFO_EXTENSION));
            if (!in_array($ext, ['csv', 'xlsx'], true)) {
                throw new RuntimeException('Formato invalido. Use apenas .csv ou .xlsx.');
            }
            $tmp = (string) $_FILES['sheet']['tmp_name'];
            if (!is_uploaded_file($tmp)) {
                throw new RuntimeException('Upload invalido.');
            }
            $summary = $service->importSpreadsheet($tmp, $originalName);
            $_SESSION['import_summary'] = $summary;
            $_SESSION['import_summary_errors'] = $service->importErrors((int) $summary['import_id'], 40);
            $msg = 'Importação concluída: ' . $summary['imported_rows'] . ' vagas importadas.';
            if (!empty($summary['ignored_rows'])) {
                $msg .= ' Ignoradas: ' . (int) $summary['ignored_rows'] . '.';
            }
            if (!empty($summary['error_rows'])) {
                $msg .= ' Com erro: ' . (int) $summary['error_rows'] . '.';
            }
            if (!empty($summary['city_warning_rows'])) {
                $msg .= ' Cidades fora do RJ: ' . (int) $summary['city_warning_rows'] . '.';
            }
            $_SESSION['flash_ok'] = $msg;
        } catch (Throwable $e) {
            $_SESSION['flash_error'] = $e->getMessage();
        }
        redirect('/admin/import');
    }

    if ($path === '/admin/jobs') {
        $jobs = $service->jobList([
            'page' => (int) ($_GET['page'] ?? 1),
            'perPage' => 15,
            'q' => (string) ($_GET['q'] ?? ''),
            'city' => (string) ($_GET['city'] ?? ''),
            'company' => (string) ($_GET['company'] ?? ''),
            'category' => (string) ($_GET['category'] ?? ''),
            'status' => (string) ($_GET['status'] ?? ''),
            'includeInactive' => true,
        ]);
        $jobs['basePath'] = '/admin/jobs';
        $jobs['query'] = array_filter([
            'q' => (string) ($_GET['q'] ?? ''),
            'city' => (string) ($_GET['city'] ?? ''),
            'company' => (string) ($_GET['company'] ?? ''),
            'category' => (string) ($_GET['category'] ?? ''),
            'status' => (string) ($_GET['status'] ?? ''),
        ], static fn ($value) => $value !== '');
        $jobs['useQuery'] = true;
        render('admin/jobs', [
            'title' => 'Gerenciar vagas',
            'jobsData' => $jobs,
            'companies' => $service->companies(),
            'cities' => $service->cities(),
            'categories' => $service->categories(),
            'editJob' => isset($_GET['edit']) ? $service->jobById((int) $_GET['edit']) : null,
            'showForm' => isset($_GET['edit']) || isset($_GET['new']),
            'flashOk' => $_SESSION['flash_ok'] ?? null,
            'flashError' => $_SESSION['flash_error'] ?? null,
            'pageType' => 'admin',
            'disableAds' => true,
        ], 'admin_layout');
        unset($_SESSION['flash_ok'], $_SESSION['flash_error']);
        exit;
    }

    if ($path === '/admin/companies') {
        render('admin/companies', [
            'title' => 'Empresas',
            'companies' => $service->companiesWithStats(),
            'editCompany' => isset($_GET['edit']) ? $service->companyById((int) $_GET['edit']) : null,
            'showForm' => isset($_GET['edit']) || isset($_GET['new']),
            'flashOk' => $_SESSION['flash_ok'] ?? null,
            'flashError' => $_SESSION['flash_error'] ?? null,
            'pageType' => 'admin',
            'disableAds' => true,
        ], 'admin_layout');
        unset($_SESSION['flash_ok'], $_SESSION['flash_error']);
        exit;
    }

    if ($path === '/admin/categories') {
        render('admin/categories', [
            'title' => 'Categorias',
            'categories' => $service->categoriesWithStats(),
            'editCategory' => isset($_GET['edit']) ? $service->categoryById((int) $_GET['edit']) : null,
            'showForm' => isset($_GET['edit']) || isset($_GET['new']),
            'flashOk' => $_SESSION['flash_ok'] ?? null,
            'flashError' => $_SESSION['flash_error'] ?? null,
            'pageType' => 'admin',
            'disableAds' => true,
        ], 'admin_layout');
        unset($_SESSION['flash_ok'], $_SESSION['flash_error']);
        exit;
    }

    if ($path === '/admin/blog/seed' && $method === 'POST') {
        if (!verify_csrf($_POST['_csrf'] ?? null)) {
            $_SESSION['flash_error'] = 'Token inválido.';
            redirect('/admin/blog/posts');
        }
        try {
            $result = $service->seedBlogContent(!empty($_POST['force']));
            $_SESSION['flash_ok'] = 'Blog atualizado: ' . (int) ($result['posts'] ?? 0) . ' artigos, '
                . (int) ($result['categories'] ?? 0) . ' categorias.';
        } catch (Throwable $e) {
            $_SESSION['flash_error'] = $e->getMessage();
        }
        redirect('/admin/blog/posts');
    }

    if ($path === '/admin/blog/categories' && $method === 'POST' && empty($_POST['_action'])) {
        if (!verify_csrf($_POST['_csrf'] ?? null)) {
            $_SESSION['flash_error'] = 'Token inválido.';
            redirect('/admin/blog/categories');
        }
        try {
            $service->createBlogCategory($_POST);
            $_SESSION['flash_ok'] = 'Categoria do blog cadastrada.';
        } catch (Throwable $e) {
            $_SESSION['flash_error'] = $e->getMessage();
        }
        redirect('/admin/blog/categories');
    }

    if (preg_match('#^/admin/blog/categories/(\d+)/edit$#', $path, $matches) && $method === 'POST') {
        if (!verify_csrf($_POST['_csrf'] ?? null)) {
            $_SESSION['flash_error'] = 'Token inválido.';
            redirect('/admin/blog/categories');
        }
        try {
            $service->updateBlogCategory((int) $matches[1], $_POST);
            $_SESSION['flash_ok'] = 'Categoria do blog atualizada.';
        } catch (Throwable $e) {
            $_SESSION['flash_error'] = $e->getMessage();
        }
        redirect('/admin/blog/categories');
    }

    if (preg_match('#^/admin/blog/categories/(\d+)/delete$#', $path, $matches) && $method === 'POST') {
        if (verify_csrf($_POST['_csrf'] ?? null)) {
            try {
                $service->deleteBlogCategory((int) $matches[1]);
                $_SESSION['flash_ok'] = 'Categoria do blog removida.';
            } catch (Throwable $e) {
                $_SESSION['flash_error'] = $e->getMessage();
            }
        }
        redirect('/admin/blog/categories');
    }

    if ($path === '/admin/blog/posts/new' && $method === 'POST') {
        if (!verify_csrf($_POST['_csrf'] ?? null)) {
            $_SESSION['flash_error'] = 'Token inválido.';
            redirect('/admin/blog/posts');
        }
        try {
            $service->saveBlogPost($_POST);
            $_SESSION['flash_ok'] = 'Artigo publicado.';
        } catch (Throwable $e) {
            $_SESSION['flash_error'] = $e->getMessage();
        }
        redirect('/admin/blog/posts');
    }

    if (preg_match('#^/admin/blog/posts/(\d+)/edit$#', $path, $matches) && $method === 'POST') {
        if (!verify_csrf($_POST['_csrf'] ?? null)) {
            $_SESSION['flash_error'] = 'Token inválido.';
            redirect('/admin/blog/posts');
        }
        try {
            $service->saveBlogPost($_POST, (int) $matches[1]);
            $_SESSION['flash_ok'] = 'Artigo atualizado.';
        } catch (Throwable $e) {
            $_SESSION['flash_error'] = $e->getMessage();
        }
        redirect('/admin/blog/posts');
    }

    if (preg_match('#^/admin/blog/posts/(\d+)/delete$#', $path, $matches) && $method === 'POST') {
        if (verify_csrf($_POST['_csrf'] ?? null)) {
            $service->deleteBlogPost((int) $matches[1]);
            $_SESSION['flash_ok'] = 'Artigo removido.';
        }
        redirect('/admin/blog/posts');
    }

    if (preg_match('#^/admin/blog/posts/(\d+)/toggle$#', $path, $matches) && $method === 'POST') {
        if (verify_csrf($_POST['_csrf'] ?? null)) {
            $service->toggleBlogPost((int) $matches[1]);
            $_SESSION['flash_ok'] = 'Status do artigo atualizado.';
        }
        redirect('/admin/blog/posts');
    }

    if ($path === '/admin/blog/categories') {
        render('admin/blog_categories', [
            'title' => 'Categorias do blog',
            'categories' => $service->blogCategories(false),
            'editCategory' => isset($_GET['edit']) ? $service->blogCategoryById((int) $_GET['edit']) : null,
            'showForm' => isset($_GET['edit']) || isset($_GET['new']),
            'flashOk' => $_SESSION['flash_ok'] ?? null,
            'flashError' => $_SESSION['flash_error'] ?? null,
            'pageType' => 'admin',
            'disableAds' => true,
        ], 'admin_layout');
        unset($_SESSION['flash_ok'], $_SESSION['flash_error']);
        exit;
    }

    if ($path === '/admin/blog/posts') {
        $categoryFilter = (string) ($_GET['category'] ?? '');
        $postsData = $service->articleList([
            'page' => (int) ($_GET['page'] ?? 1),
            'perPage' => (int) config('blog.admin_per_page', 20),
            'category' => $categoryFilter !== '' ? $categoryFilter : null,
            'q' => (string) ($_GET['q'] ?? ''),
            'activeOnly' => false,
        ]);
        $postsData['basePath'] = '/admin/blog/posts';
        $postsData['query'] = array_filter([
            'category' => $categoryFilter,
            'q' => (string) ($_GET['q'] ?? ''),
        ], static fn ($v) => $v !== '');
        $postsData['useQuery'] = true;
        render('admin/blog_posts', [
            'title' => 'Artigos do blog',
            'postsData' => $postsData,
            'posts' => $postsData['articles'],
            'blogCategories' => $service->blogCategories(false),
            'categoryFilter' => $categoryFilter,
            'searchQuery' => (string) ($_GET['q'] ?? ''),
            'editPost' => isset($_GET['edit']) ? $service->blogPostById((int) $_GET['edit']) : null,
            'showForm' => isset($_GET['edit']) || isset($_GET['new']),
            'flashOk' => $_SESSION['flash_ok'] ?? null,
            'flashError' => $_SESSION['flash_error'] ?? null,
            'pageType' => 'admin',
            'disableAds' => true,
        ], 'admin_layout');
        unset($_SESSION['flash_ok'], $_SESSION['flash_error']);
        exit;
    }

    if ($path === '/admin/import') {
        $importSummary = $_SESSION['import_summary'] ?? null;
        $importSummaryErrors = $_SESSION['import_summary_errors'] ?? [];
        unset($_SESSION['import_summary'], $_SESSION['import_summary_errors']);
        render('admin/import', [
            'title' => 'Importação de planilha',
            'stats' => $service->dashboardStats(),
            'importSummary' => $importSummary,
            'importSummaryErrors' => $importSummaryErrors,
            'flashOk' => $_SESSION['flash_ok'] ?? null,
            'flashError' => $_SESSION['flash_error'] ?? null,
            'pageType' => 'admin',
            'disableAds' => true,
        ], 'admin_layout');
        unset($_SESSION['flash_ok'], $_SESSION['flash_error']);
        exit;
    }

    http_response_code(404);
    render('pages/404', ['title' => 'Página não encontrada', 'disableAds' => true, 'pageType' => 'error', 'robots' => 'noindex,follow']);
    exit;
}

$common = [
    'siteName' => config('site.name'),
    'uf' => config('site.main_uf'),
    'stateName' => config('site.main_state_name'),
    'citiesMenu' => array_slice($service->citiesWithStats(), 0, 8),
    'categoriesMenu' => array_slice($service->categories(), 0, 8),
];

if ($path === '/') {
    $data = $service->homeData();
    render('pages/home', array_merge($common, [
        'title' => 'Vagas RJ - Empregos no Rio de Janeiro',
        'description' => 'Encontre vagas de emprego no Rio de Janeiro (RJ) por cidade, empresa e categoria no Vagas RJ.',
        'canonical' => base_url('/'),
        'pageType' => 'home',
        ...$data,
    ]));
    exit;
}

if ($path === '/vagas' && !empty($_GET['page']) && (int) $_GET['page'] > 1) {
    $query = $_GET;
    unset($query['page']);
    redirect(pagination_build_url('/vagas', (int) $_GET['page'], $query));
}

$renderJobsListing = static function (int $page, array $filterGet) use ($service, $common): void {
    $filters = [
        'page' => $page,
        'q' => (string) ($filterGet['q'] ?? ''),
        'city' => (string) ($filterGet['city'] ?? ''),
        'company' => (string) ($filterGet['company'] ?? ''),
        'category' => (string) ($filterGet['category'] ?? ''),
    ];
    $jobsData = $service->jobList($filters);
    $jobsData['basePath'] = '/vagas';
    $jobsData['query'] = array_filter([
        'q' => $filters['q'],
        'city' => $filters['city'],
        'company' => $filters['company'],
        'category' => $filters['category'],
    ], static fn ($v) => $v !== '');

    $baseTitle = 'Vagas no Rio de Janeiro RJ - Vagas RJ';
    $baseDescription = 'Listagem de vagas de emprego no Rio de Janeiro com filtros por cidade, empresa e categoria.';
    $meta = pagination_meta($page, (int) $jobsData['totalPages'], $baseTitle, $baseDescription);
    $hasFilters = $filters['q'] !== '' || $filters['city'] !== '' || $filters['company'] !== '' || $filters['category'] !== '';
    $emptyPage = $jobsData['total'] === 0;
    $robots = ($emptyPage && ($hasFilters || $page > 1)) ? 'noindex,follow' : 'index,follow';

    render('pages/jobs', array_merge($common, [
        'title' => $meta['title'],
        'description' => $meta['description'],
        'canonical' => base_url(pagination_build_url('/vagas', $page, $jobsData['query'])),
        'robots' => $robots,
        'pageType' => 'jobs',
        'jobsData' => $jobsData,
        'companies' => $service->companies(),
        'cities' => $service->cities(),
        'categories' => $service->categories(),
        'filters' => $filterGet,
    ]));
};

if (preg_match('#^/vagas/pagina/(\d+)$#', $path, $matches)) {
    $renderJobsListing(max(1, (int) $matches[1]), $_GET);
    exit;
}

if ($path === '/vagas') {
    $renderJobsListing(1, $_GET);
    exit;
}

if (preg_match('#^/vaga/([a-z0-9\-]+)$#', $path, $matches)) {
    redirect('/vagas/' . $matches[1]);
}

if (preg_match('#^/vagas/([a-z0-9\-]+)$#', $path, $matches)) {
    $job = $service->jobBySlug($matches[1]);
    if (!$job) {
        http_response_code(404);
        render('pages/404', array_merge($common, ['title' => 'Vaga não encontrada', 'pageType' => 'error', 'disableAds' => true, 'robots' => 'noindex,follow']));
        exit;
    }

    render('pages/job_detail', array_merge($common, [
        'title' => $job['title'] . ' - ' . $job['city_name'] . '/RJ | Vagas RJ',
        'description' => excerpt($job['description'], 150),
        'canonical' => base_url('/vagas/' . $job['slug']),
        'pageType' => 'job_detail',
        'job' => $job,
        'relatedJobs' => $service->relatedJobs($job),
        'relatedArticles' => $service->relatedBlogPosts(null, 0, 3),
    ]));
    exit;
}

if ($path === '/cidades') {
    render('pages/cities', array_merge($common, [
        'title' => 'Cidades do Rio de Janeiro com vagas - Vagas RJ',
        'description' => 'Veja cidades do RJ com oportunidades de emprego e acesse vagas por município.',
        'canonical' => base_url('/cidades'),
        'pageType' => 'city',
        'cities' => $service->citiesWithStats(),
    ]));
    exit;
}

if (preg_match('#^/cidade/([a-z0-9\-]+)$#', $path, $matches)) {
    redirect('/cidades/' . $matches[1]);
}

if (preg_match('#^/cidades/([a-z0-9\-]+)$#', $path, $matches)) {
    $city = $service->cityBySlug($matches[1]);
    if (!$city) {
        http_response_code(404);
        render('pages/404', array_merge($common, ['title' => 'Cidade não encontrada', 'pageType' => 'error', 'disableAds' => true, 'robots' => 'noindex,follow']));
        exit;
    }

    $jobsData = $service->jobList([
        'page' => (int) ($_GET['page'] ?? 1),
        'city' => $city['slug'],
    ]);
    render('pages/city', array_merge($common, [
        'title' => 'Vagas em ' . $city['name'] . ' RJ - Vagas RJ',
        'description' => 'Vagas atualizadas em ' . $city['name'] . '/' . $city['state'] . '.',
        'canonical' => base_url('/cidades/' . $city['slug']),
        'pageType' => 'city',
        'city' => $city,
        'jobsData' => $jobsData,
        'cities' => $service->citiesWithStats(),
        'categories' => $service->categories(),
        'relatedArticles' => $service->blogPostsForCity((string) $city['name'], 4),
    ]));
    exit;
}

if ($path === '/empresas') {
    render('pages/companies', array_merge($common, [
        'title' => 'Empresas com vagas no Rio de Janeiro - Vagas RJ',
        'description' => 'Veja empresas com oportunidades abertas no estado do Rio de Janeiro.',
        'canonical' => base_url('/empresas'),
        'pageType' => 'company',
        'companies' => $service->companiesWithStats(),
    ]));
    exit;
}

if (preg_match('#^/empresa/([a-z0-9\-]+)$#', $path, $matches)) {
    redirect('/empresas/' . $matches[1]);
}

if (preg_match('#^/empresas/([a-z0-9\-]+)$#', $path, $matches)) {
    $company = $service->companyBySlug($matches[1]);
    if (!$company) {
        http_response_code(404);
        render('pages/404', array_merge($common, ['title' => 'Empresa não encontrada', 'pageType' => 'error', 'disableAds' => true, 'robots' => 'noindex,follow']));
        exit;
    }
    $jobsData = $service->jobList([
        'page' => (int) ($_GET['page'] ?? 1),
        'company' => $company['slug'],
    ]);
    render('pages/company_detail', array_merge($common, [
        'title' => 'Vagas na ' . $company['name'] . ' - Rio de Janeiro | Vagas RJ',
        'description' => 'Oportunidades da ' . $company['name'] . ' no estado do Rio de Janeiro.',
        'canonical' => base_url('/empresas/' . $company['slug']),
        'pageType' => 'company',
        'company' => $company,
        'jobsData' => $jobsData,
    ]));
    exit;
}

if ($path === '/categorias') {
    render('pages/categories', array_merge($common, [
        'title' => 'Categorias de vagas no Rio de Janeiro - Vagas RJ',
        'description' => 'Navegue por categorias de vagas de emprego no estado do Rio de Janeiro.',
        'canonical' => base_url('/categorias'),
        'pageType' => 'category',
        'categories' => $service->categoriesWithStats(),
    ]));
    exit;
}

if (preg_match('#^/categoria/([a-z0-9\-]+)$#', $path, $matches)) {
    redirect('/categorias/' . $matches[1]);
}

if (preg_match('#^/categorias/([a-z0-9\-]+)$#', $path, $matches)) {
    $category = $service->categoryBySlug($matches[1]);
    if (!$category) {
        http_response_code(404);
        render('pages/404', array_merge($common, ['title' => 'Categoria não encontrada', 'pageType' => 'error', 'disableAds' => true, 'robots' => 'noindex,follow']));
        exit;
    }
    $jobsData = $service->jobList([
        'page' => (int) ($_GET['page'] ?? 1),
        'category' => $category['slug'],
    ]);
    render('pages/category_detail', array_merge($common, [
        'title' => 'Vagas de ' . $category['name'] . ' no Rio de Janeiro RJ - Vagas RJ',
        'description' => 'Oportunidades da categoria ' . $category['name'] . ' no estado do Rio de Janeiro.',
        'canonical' => base_url('/categorias/' . $category['slug']),
        'pageType' => 'category',
        'category' => $category,
        'jobsData' => $jobsData,
        'cities' => $service->citiesWithStats(),
        'relatedArticles' => $service->blogPostsForJobCategory((string) $category['name'], 4),
    ]));
    exit;
}

if ($path === '/blog' && !empty($_GET['page']) && (int) $_GET['page'] > 1) {
    redirect(pagination_build_url('/blog', (int) $_GET['page']));
}

$renderBlogListing = static function (int $page, ?array $blogCategory) use ($service, $common): void {
    $categorySlug = $blogCategory['slug'] ?? null;
    $basePath = $categorySlug ? '/blog/categoria/' . $categorySlug : '/blog';
    $articlesData = $service->articleList([
        'page' => $page,
        'category' => $categorySlug,
    ]);
    $articlesData['basePath'] = $basePath;
    $articlesData['query'] = [];

    $baseTitle = $blogCategory
        ? ($blogCategory['name'] . ' - Blog Vagas RJ')
        : 'Blog de carreira no Rio de Janeiro - Vagas RJ';
    $baseDescription = $blogCategory
        ? ((string) ($blogCategory['description'] ?: 'Artigos sobre ' . $blogCategory['name']))
        : 'Dicas de currículo, entrevista e mercado de trabalho no RJ.';
    $meta = pagination_meta($page, (int) $articlesData['totalPages'], $baseTitle, $baseDescription);
    $robots = ($articlesData['total'] === 0 && $page > 1) ? 'noindex,follow' : 'index,follow';
    $template = $blogCategory ? 'pages/blog_category' : 'pages/blog';

    render($template, array_merge($common, [
        'title' => $meta['title'],
        'description' => $meta['description'],
        'canonical' => base_url(pagination_build_url($basePath, $page)),
        'robots' => $robots,
        'pageType' => 'blog',
        'articles' => $articlesData['articles'],
        'articlesData' => $articlesData,
        'blogCategories' => $service->blogCategories(),
        'blogCategory' => $blogCategory,
    ]));
};

if (preg_match('#^/blog/categoria/([a-z0-9\-]+)/pagina/(\d+)$#', $path, $matches)) {
    $blogCategory = $service->blogCategoryBySlug($matches[1]);
    if (!$blogCategory) {
        http_response_code(404);
        render('pages/404', array_merge($common, ['title' => 'Categoria não encontrada', 'pageType' => 'error', 'disableAds' => true, 'robots' => 'noindex,follow']));
        exit;
    }
    $renderBlogListing(max(1, (int) $matches[2]), $blogCategory);
    exit;
}

if (preg_match('#^/blog/categoria/([a-z0-9\-]+)$#', $path, $matches)) {
    $blogCategory = $service->blogCategoryBySlug($matches[1]);
    if (!$blogCategory) {
        http_response_code(404);
        render('pages/404', array_merge($common, ['title' => 'Categoria não encontrada', 'pageType' => 'error', 'disableAds' => true, 'robots' => 'noindex,follow']));
        exit;
    }
    $renderBlogListing(1, $blogCategory);
    exit;
}

if (preg_match('#^/blog/pagina/(\d+)$#', $path, $matches)) {
    $renderBlogListing(max(1, (int) $matches[1]), null);
    exit;
}

if ($path === '/blog') {
    $renderBlogListing(1, null);
    exit;
}

if (preg_match('#^/blog/([a-z0-9\-]+)$#', $path, $matches)) {
    $article = $service->articleBySlug($matches[1]);
    if (!$article) {
        http_response_code(404);
        render('pages/404', array_merge($common, ['title' => 'Artigo não encontrado', 'pageType' => 'error', 'disableAds' => true, 'robots' => 'noindex,follow']));
        exit;
    }
    render('pages/article_detail', array_merge($common, [
        'title' => (string) ($article['seo_title'] ?: $article['title']),
        'description' => (string) ($article['seo_description'] ?: $article['excerpt']),
        'canonical' => base_url('/blog/' . $article['slug']),
        'pageType' => 'article',
        'article' => $article,
        'relatedArticles' => $service->relatedBlogPosts((int) $article['category_id'], (int) $article['id'], 5),
        'blogCategories' => $service->blogCategories(),
    ]));
    exit;
}

$institutionalMap = [
    '/sobre' => ['Sobre o Vagas RJ', 'Conheça o portal regional de vagas no Rio de Janeiro.'],
    '/contato' => ['Contato', 'Fale conosco para dúvidas, sugestões e correções.'],
    '/politica-de-privacidade' => ['Política de Privacidade', 'Como tratamos dados, cookies, analytics e publicidade.'],
    '/politica-de-cookies' => ['Política de Cookies', 'Cookies essenciais, análise e publicidade no Vagas RJ.'],
    '/termos-de-uso' => ['Termos de Uso', 'Regras de uso do portal de divulgação de vagas.'],
    '/aviso-legal' => ['Aviso Legal', 'Informações legais sobre o Vagas RJ.'],
    '/seguranca-para-candidatos' => ['Segurança para candidatos', 'Orientações contra golpes em processos seletivos.'],
    '/mapa-do-site' => ['Mapa do site', 'Navegação completa do Vagas RJ.'],
];

if (array_key_exists($path, $institutionalMap)) {
    [$title, $description] = $institutionalMap[$path];
    $extra = [];
    if ($path === '/mapa-do-site') {
        $extra = [
            'mapCities' => $service->cities(),
            'mapCategories' => $service->categories(),
            'mapCompanies' => array_slice($service->companies(), 0, 40),
            'mapBlogCategories' => $service->blogCategories(),
            'mapRecentArticles' => $service->articles(12),
        ];
    }
    render('pages/institutional', array_merge($common, [
        'title' => $title . ' - ' . config('site.name'),
        'description' => $description,
        'canonical' => base_url($path),
        'pageType' => 'institutional',
        'institutionalType' => trim($path, '/'),
    ], $extra));
    exit;
}

http_response_code(404);
render('pages/404', array_merge($common, ['title' => 'Página não encontrada', 'pageType' => 'error', 'disableAds' => true, 'robots' => 'noindex,follow']));
