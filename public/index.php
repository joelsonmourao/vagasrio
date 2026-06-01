<?php

declare(strict_types=1);

require dirname(__DIR__) . '/app/bootstrap.php';

use App\Services\PortalService;

$service = new PortalService(db());
$path = current_path();
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if ($path === '/sitemap.xml') {
    header('Content-Type: application/xml; charset=UTF-8');
    $urls = $service->buildSitemapUrls();
    echo '<?xml version="1.0" encoding="UTF-8"?>';
    echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
    foreach ($urls as $url) {
        echo '<url><loc>' . e($url['loc']) . '</loc><priority>' . e($url['priority']) . '</priority></url>';
    }
    echo '</urlset>';
    exit;
}

if ($path === '/robots.txt') {
    header('Content-Type: text/plain; charset=UTF-8');
    echo "User-agent: *\nAllow: /\nDisallow: /admin\nDisallow: /scripts\nSitemap: " . base_url('/sitemap.xml');
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

    if (preg_match('#^/admin/jobs/(\d+)/delete$#', $path, $matches) && $method === 'POST') {
        if (verify_csrf($_POST['_csrf'] ?? null)) {
            $service->deleteJob((int) $matches[1]);
            $_SESSION['flash_ok'] = 'Vaga removida.';
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

if ($path === '/vagas') {
    $jobsData = $service->jobList([
        'page' => (int) ($_GET['page'] ?? 1),
        'q' => (string) ($_GET['q'] ?? ''),
        'city' => (string) ($_GET['city'] ?? ''),
        'company' => (string) ($_GET['company'] ?? ''),
        'category' => (string) ($_GET['category'] ?? ''),
    ]);
    render('pages/jobs', array_merge($common, [
        'title' => 'Vagas no Rio de Janeiro RJ - Vagas RJ',
        'description' => 'Listagem de vagas de emprego no Rio de Janeiro com filtros por cidade, empresa e categoria.',
        'canonical' => base_url('/vagas'),
        'robots' => ($jobsData['total'] === 0 && (!empty($_GET['q']) || !empty($_GET['city']) || !empty($_GET['company']) || !empty($_GET['category']))) ? 'noindex,follow' : 'index,follow',
        'pageType' => 'jobs',
        'jobsData' => $jobsData,
        'companies' => $service->companies(),
        'cities' => $service->cities(),
        'categories' => $service->categories(),
        'filters' => $_GET,
    ]));
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
        'canonical' => base_url('/cidade/' . $city['slug']),
        'pageType' => 'city',
        'city' => $city,
        'jobsData' => $jobsData,
        'cities' => $service->citiesWithStats(),
    ]));
    exit;
}

if ($path === '/empresas') {
    render('pages/companies', array_merge($common, [
        'title' => 'Empresas com vagas no Rio de Janeiro - Vagas RJ',
        'description' => 'Veja empresas com oportunidades abertas no estado do Rio de Janeiro.',
        'canonical' => base_url('/empresas'),
        'pageType' => 'company',
        'companies' => $service->companies(),
    ]));
    exit;
}

if (preg_match('#^/empresa/([a-z0-9\-]+)$#', $path, $matches)) {
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
        'canonical' => base_url('/empresa/' . $company['slug']),
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
        'categories' => $service->categories(),
    ]));
    exit;
}

if (preg_match('#^/categoria/([a-z0-9\-]+)$#', $path, $matches)) {
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
        'canonical' => base_url('/categoria/' . $category['slug']),
        'pageType' => 'category',
        'category' => $category,
        'jobsData' => $jobsData,
    ]));
    exit;
}

if ($path === '/blog') {
    render('pages/blog', array_merge($common, [
        'title' => 'Blog de carreira no Rio de Janeiro - Vagas RJ',
        'description' => 'Dicas de carreira, currículo e mercado de trabalho.',
        'canonical' => base_url('/blog'),
        'pageType' => 'blog',
        'articles' => $service->articles(),
    ]));
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
        'title' => $article['title'],
        'description' => $article['excerpt'],
        'canonical' => base_url('/blog/' . $article['slug']),
        'pageType' => 'article',
        'article' => $article,
    ]));
    exit;
}

$institutionalMap = [
    '/sobre' => ['Sobre', 'Conheça o portal e nossa missão.'],
    '/contato' => ['Contato', 'Entre em contato para dúvidas e suporte.'],
    '/politica-de-privacidade' => ['Política de Privacidade', 'Como tratamos dados, cookies e publicidade.'],
    '/politica-de-cookies' => ['Política de Cookies', 'Informações sobre cookies, consentimento e parceiros.'],
    '/termos-de-uso' => ['Termos de Uso', 'Regras de uso e responsabilidades do portal.'],
];

if (array_key_exists($path, $institutionalMap)) {
    [$title, $description] = $institutionalMap[$path];
    render('pages/institutional', array_merge($common, [
        'title' => $title . ' - ' . config('site.name'),
        'description' => $description,
        'canonical' => base_url($path),
        'pageType' => 'institutional',
        'institutionalType' => trim($path, '/'),
    ]));
    exit;
}

http_response_code(404);
render('pages/404', array_merge($common, ['title' => 'Página não encontrada', 'pageType' => 'error', 'disableAds' => true, 'robots' => 'noindex,follow']));
