<?php

declare(strict_types=1);

use App\Services\Database;

function config(?string $key = null, mixed $default = null): mixed
{
    static $config;
    if (!$config) {
        $config = require ROOT_PATH . '/config/app.php';
    }

    if ($key === null) {
        return $config;
    }

    $segments = explode('.', $key);
    $value = $config;
    foreach ($segments as $segment) {
        if (!is_array($value) || !array_key_exists($segment, $value)) {
            return $default;
        }
        $value = $value[$segment];
    }
    return $value;
}

function db(): PDO
{
    return Database::pdo();
}

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function slugify(string $text): string
{
    $text = trim($text);
    $text = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text) ?: $text;
    $text = strtolower($text);
    $text = preg_replace('/[^a-z0-9]+/', '-', $text) ?? '';
    return trim($text, '-');
}

function city_slug(string $cityName): string
{
    return slugify($cityName . ' RJ');
}

/** @return list<string> */
function allowed_rj_cities(): array
{
    $cities = config('site.allowed_cities', []);
    return is_array($cities) ? array_values($cities) : [];
}

function match_allowed_city_name(string $name): ?string
{
    $normalized = smart_title(normalize_spaces($name));
    foreach (allowed_rj_cities() as $city) {
        if (mb_strtolower(smart_title($city)) === mb_strtolower($normalized)) {
            return smart_title($city);
        }
    }
    return null;
}

function is_allowed_rj_city(string $name): bool
{
    return match_allowed_city_name($name) !== null;
}

function nullable_field(string $value): ?string
{
    $value = normalize_spaces($value);
    return $value === '' ? null : $value;
}

function normalize_spaces(string $text): string
{
    $text = preg_replace('/\s+/u', ' ', trim($text)) ?? trim($text);
    return $text;
}

function smart_title(string $text): string
{
    $text = normalize_spaces($text);
    $upperRatio = strlen(preg_replace('/[^A-Z]/', '', $text) ?? '') / max(strlen($text), 1);
    if ($upperRatio > 0.55) {
        $text = mb_convert_case(mb_strtolower($text), MB_CASE_TITLE, 'UTF-8');
    }
    return $text;
}

function base_url(string $path = ''): string
{
    $base = rtrim((string) config('site.base_url'), '/');
    if ($path === '') {
        return $base;
    }
    return $base . '/' . ltrim($path, '/');
}

function url_path(string $path = '/'): string
{
    if ($path === '' || $path === '/') {
        return '/';
    }

    return '/' . ltrim($path, '/');
}

function city_public_path(string $slug): string
{
    return url_path('/cidades/' . ltrim($slug, '/'));
}

function company_public_path(string $slug): string
{
    return url_path('/empresas/' . ltrim($slug, '/'));
}

function category_public_path(string $slug): string
{
    return url_path('/categorias/' . ltrim($slug, '/'));
}

function blog_category_public_path(string $slug): string
{
    return url_path('/blog/categoria/' . ltrim($slug, '/'));
}

function city_page_intro(string $cityName): string
{
    return 'O Vagas RJ reúne oportunidades divulgadas em ' . $cityName
        . ', no estado do Rio de Janeiro. Use os filtros abaixo para encontrar vagas compatíveis com seu perfil, '
        . 'leia a descrição completa de cada anúncio e candidate-se apenas pelos canais oficiais informados pela empresa contratante. '
        . 'O portal não participa do processo seletivo e não cobra taxa de candidatura.';
}

function category_page_intro(string $categoryName): string
{
    return 'Confira vagas da área ' . $categoryName
        . ' em cidades do Rio de Janeiro. Cada oportunidade listada possui descrição própria e link ou e-mail de candidatura '
        . 'fornecido pela empresa. Antes de enviar currículo, confirme requisitos, local de trabalho e modalidade de contratação '
        . 'no anúncio original.';
}

function company_page_disclaimer(): string
{
    return 'O Vagas RJ apenas divulga oportunidades publicadas ou cadastradas por terceiros. '
        . 'Não representamos a empresa, não garantimos contratação e não cobramos taxa para candidatura. '
        . 'Confirme informações no site oficial ou no contato informado na vaga.';
}

function pagination_build_url(string $basePath, int $page, array $query = []): string
{
    unset($query['page']);
    $query = array_filter($query, static fn ($v) => $v !== null && $v !== '');

    if ($page <= 1) {
        $url = url_path($basePath);

        return $query !== [] ? $url . '?' . http_build_query($query) : $url;
    }

    $url = url_path(rtrim($basePath, '/') . '/pagina/' . $page);

    return $query !== [] ? $url . '?' . http_build_query($query) : $url;
}

function pagination_parse_page(string $path, string $basePath): int
{
    $pattern = '#^' . preg_quote(rtrim($basePath, '/'), '#') . '/pagina/(\d+)$#';
    if (preg_match($pattern, $path, $matches)) {
        return max(1, (int) $matches[1]);
    }

    return max(1, (int) ($_GET['page'] ?? 1));
}

function pagination_meta(int $page, int $totalPages, string $baseTitle, string $baseDescription): array
{
    if ($page <= 1) {
        return ['title' => $baseTitle, 'description' => $baseDescription];
    }

    return [
        'title' => $baseTitle . ' - Página ' . $page,
        'description' => $baseDescription . ' Página ' . $page . ' de ' . max(1, $totalPages) . '.',
    ];
}

function article_content_with_mid_ad(string $html, string $pageType = 'article'): string
{
    $count = 0;
    $inserted = false;

    return preg_replace_callback('/<\/p>/i', static function () use (&$count, &$inserted, $pageType) {
        $count++;
        $replacement = '</p>';
        if (!$inserted && $count === 2) {
            $inserted = true;
            $replacement .= ad_slot('blog_after_intro', $pageType, 970, 110);
        }

        return $replacement;
    }, $html) ?? $html;
}

function current_path(): string
{
    $uri = strtok($_SERVER['REQUEST_URI'] ?? '/', '?');
    return '/' . trim((string) $uri, '/');
}

function is_active_menu(string $path): bool
{
    $current = current_path();
    if ($path === '/') {
        return $current === '/';
    }
    return $current === $path || str_starts_with($current, rtrim($path, '/') . '/');
}

function csrf_token(): string
{
    if (empty($_SESSION['_csrf'])) {
        $_SESSION['_csrf'] = bin2hex(random_bytes(24));
    }
    return $_SESSION['_csrf'];
}

function verify_csrf(?string $token): bool
{
    return is_string($token) && isset($_SESSION['_csrf']) && hash_equals($_SESSION['_csrf'], $token);
}

function render(string $template, array $data = [], string $layout = 'layout'): void
{
    $GLOBALS['__ads_used'] = false;
    $GLOBALS['__disable_ads'] = !empty($data['disableAds']);

    extract($data, EXTR_SKIP);
    $templatePath = ROOT_PATH . '/templates/' . $template . '.php';
    if (!is_file($templatePath)) {
        http_response_code(500);
        echo 'Template nao encontrado.';
        return;
    }

    ob_start();
    require $templatePath;
    $content = ob_get_clean();

    require ROOT_PATH . '/templates/' . $layout . '.php';
}

function redirect(string $path): never
{
    if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
        header('Location: ' . $path);
    } else {
        header('Location: ' . url_path($path));
    }
    exit;
}

function clean_html(?string $html): string
{
    if (!$html) {
        return '';
    }
    $allowed = '<p><br><ul><ol><li><strong><em><b><i><h2><h3><h4>';
    return trim(strip_tags($html, $allowed));
}

function normalize_category_name(string $name): string
{
    $name = smart_title(normalize_spaces($name));
    $aliases = [
        'logistica' => 'Logística',
        'logística' => 'Logística',
    ];
    $key = mb_strtolower($name);
    return $aliases[$key] ?? $name;
}

function html_to_plain_text(string $html): string
{
    $html = preg_replace('/<\s*br\s*\/?>/iu', ' ', $html) ?? $html;
    $html = preg_replace('/<\s*\/\s*(p|div|li|h[1-6]|tr|td|th|blockquote)\s*>/iu', ' ', $html) ?? $html;
    $html = preg_replace('/<\s*(p|div|li|h[1-6]|blockquote)\b[^>]*>/iu', ' ', $html) ?? $html;
    $text = trim(strip_tags($html));
    $text = preg_replace('/\s+/u', ' ', $text) ?? $text;
    $text = preg_replace('/([.!?;:])([^\s\d])/u', '$1 $2', $text) ?? $text;
    return trim($text);
}

function excerpt(string $text, int $max = 180): string
{
    $text = html_to_plain_text($text);
    if ($text === '') {
        return '';
    }
    if (mb_strlen($text) <= $max) {
        return $text;
    }

    $window = mb_substr($text, 0, $max + 1);
    if (preg_match('/^(.*[.!?…])\s/u', $window, $matches)) {
        $sentence = trim($matches[1]);
        if (mb_strlen($sentence) >= (int) ($max * 0.45)) {
            return $sentence;
        }
    }

    $slice = mb_substr($text, 0, $max);
    $lastSpace = mb_strrpos($slice, ' ');
    if ($lastSpace !== false && $lastSpace > (int) ($max * 0.55)) {
        $slice = mb_substr($slice, 0, $lastSpace);
    }

    return rtrim($slice, '.,;:- ') . '…';
}

function format_job_description(string $content): string
{
    $content = trim($content);
    if ($content === '') {
        return '';
    }
    if (str_contains($content, '<')) {
        return clean_html($content);
    }
    $parts = preg_split('/\R{2,}/', $content) ?: [$content];
    $html = '';
    foreach ($parts as $part) {
        $part = trim($part);
        if ($part !== '') {
            $html .= '<p>' . nl2br(e($part)) . '</p>';
        }
    }
    return $html;
}

function employment_type_label(?string $type): string
{
    if (!$type) {
        return '';
    }
    $map = [
        'FULL_TIME' => 'Tempo integral',
        'PART_TIME' => 'Meio período',
        'CONTRACTOR' => 'PJ / Freelancer',
        'TEMPORARY' => 'Temporário',
        'INTERN' => 'Estágio',
        'VOLUNTEER' => 'Voluntário',
        'PER_DIEM' => 'Diária',
        'OTHER' => 'Outro',
    ];
    $key = strtoupper(trim($type));
    return $map[$key] ?? smart_title($type);
}

function site_timezone(): \DateTimeZone
{
    static $tz = null;
    if ($tz === null) {
        $tz = new \DateTimeZone((string) config('site.timezone', 'America/Sao_Paulo'));
    }

    return $tz;
}

function is_date_only(string $value): bool
{
    return (bool) preg_match('/^\d{4}-\d{2}-\d{2}$/', trim($value));
}

function parse_excel_serial_date(string $value): ?\DateTimeImmutable
{
    if (!is_numeric($value)) {
        return null;
    }
    $serial = (float) $value;
    if ($serial <= 25569) {
        return null;
    }
    $timestamp = (int) round(($serial - 25569) * 86400);

    return (new \DateTimeImmutable('@' . $timestamp))->setTimezone(site_timezone());
}

function parse_job_datetime(string $raw, string $kind = 'published'): ?\DateTimeImmutable
{
    $raw = trim($raw);
    if ($raw === '') {
        return null;
    }

    $excelDate = parse_excel_serial_date($raw);
    if ($excelDate !== null) {
        return $kind === 'valid'
            ? $excelDate->setTime(23, 59, 59)
            : $excelDate->setTime(0, 0, 0);
    }

    if (is_date_only($raw)) {
        $time = $kind === 'valid' ? '23:59:59' : '00:00:00';
        return new \DateTimeImmutable($raw . 'T' . $time, site_timezone());
    }

    try {
        return (new \DateTimeImmutable($raw))->setTimezone(site_timezone());
    } catch (\Exception) {
        $parsed = strtotime($raw);
        if ($parsed === false) {
            return null;
        }

        return (new \DateTimeImmutable('@' . $parsed))->setTimezone(site_timezone());
    }
}

function resolve_job_published_at(string $raw = ''): string
{
    $dt = parse_job_datetime($raw, 'published');
    if ($dt === null) {
        $dt = new \DateTimeImmutable('today', site_timezone());
    }

    if ($raw !== '' && is_date_only($raw)) {
        $dt = $dt->setTime(0, 0, 0);
    }

    return $dt->format('c');
}

function resolve_job_valid_through(string $raw, string $publishedAtStored): string
{
    $dt = parse_job_datetime($raw, 'valid');
    if ($dt === null) {
        $published = new \DateTimeImmutable($publishedAtStored, site_timezone());
        $days = (int) config('jobs.default_valid_days', 30);
        $dt = $published->modify('+' . $days . ' days')->setTime(23, 59, 59);
    } elseif ($raw !== '' && is_date_only($raw)) {
        $dt = $dt->setTime(23, 59, 59);
    }

    return $dt->format('c');
}

function job_schema_iso8601(\DateTimeInterface $dt): string
{
    return $dt->format('Y-m-d\TH:i:sP');
}

function job_schema_date_posted(string $stored): string
{
    $dt = new \DateTimeImmutable($stored, site_timezone());
    if ($dt->format('H:i:s') === '00:00:00') {
        $dt = $dt->setTime(0, 0, 0);
    }

    return job_schema_iso8601($dt);
}

function job_schema_valid_through(?string $stored, string $publishedAt): string
{
    if ($stored === null || trim($stored) === '') {
        $iso = resolve_job_valid_through('', $publishedAt);

        return job_schema_iso8601(new \DateTimeImmutable($iso, site_timezone()));
    }

    $dt = new \DateTimeImmutable($stored, site_timezone());
    if ($dt->format('H:i:s') === '00:00:00') {
        $dt = $dt->setTime(23, 59, 59);
    }

    return job_schema_iso8601($dt);
}

function normalize_employment_type(?string $type): ?string
{
    if ($type === null || trim($type) === '') {
        return null;
    }

    $allowed = [
        'FULL_TIME',
        'PART_TIME',
        'CONTRACTOR',
        'TEMPORARY',
        'INTERN',
        'VOLUNTEER',
        'PER_DIEM',
        'OTHER',
    ];
    $key = strtoupper(str_replace([' ', '-'], '_', trim($type)));
    $aliases = [
        'CLT' => 'FULL_TIME',
        'PJ' => 'CONTRACTOR',
        'FREELANCER' => 'CONTRACTOR',
        'ESTAGIO' => 'INTERN',
        'ESTÁGIO' => 'INTERN',
    ];

    if (isset($aliases[$key])) {
        $key = $aliases[$key];
    }

    return in_array($key, $allowed, true) ? $key : null;
}

function parse_job_salary_amount(?string $salary): ?float
{
    if ($salary === null || trim($salary) === '') {
        return null;
    }

    $normalized = preg_replace('/[^\d,\.]/', '', trim($salary)) ?? '';
    if ($normalized === '') {
        return null;
    }

    if (str_contains($normalized, ',') && str_contains($normalized, '.')) {
        $normalized = str_replace('.', '', $normalized);
        $normalized = str_replace(',', '.', $normalized);
    } elseif (str_contains($normalized, ',')) {
        $normalized = str_replace(',', '.', $normalized);
    }

    $value = (float) $normalized;

    return $value > 0 ? $value : null;
}

function city_postal_code(?string $cityName): ?string
{
    if ($cityName === null || trim($cityName) === '') {
        return null;
    }

    $map = config('site.city_postal_codes', []);
    if (!is_array($map)) {
        return null;
    }

    $code = $map[trim($cityName)] ?? null;

    return is_string($code) && $code !== '' ? $code : null;
}

function portal_logo_svg_url(): string
{
    return base_url('assets/img/logo-vagas-rj.svg');
}

function portal_default_logo_url(): string
{
    return base_url('assets/img/logo-vagas-rj.png');
}

function portal_og_image_url(): string
{
    return portal_default_logo_url();
}

function job_hiring_organization_logo(array $job): string
{
    $companyLogo = trim((string) ($job['company_logo'] ?? ''));
    if ($companyLogo !== '') {
        if (preg_match('#^https?://#i', $companyLogo)) {
            return $companyLogo;
        }

        return base_url(ltrim($companyLogo, '/'));
    }

    return portal_default_logo_url();
}

function is_intermediary_apply_url(string $url): bool
{
    $host = strtolower((string) parse_url($url, PHP_URL_HOST));
    if ($host === '') {
        return true;
    }

    $siteHost = strtolower((string) parse_url(base_url(), PHP_URL_HOST));
    if ($siteHost !== '' && ($host === $siteHost || str_ends_with($host, '.' . $siteHost))) {
        return true;
    }

    $intermediaryHosts = [
        'linkedin.com',
        'indeed.com',
        'infojobs.com.br',
        'catho.com.br',
        'gupy.io',
        'vagas.com.br',
        'trabalhabrasil.com.br',
        'empregos.com.br',
        'glassdoor.com',
        'bebee.com',
    ];

    foreach ($intermediaryHosts as $pattern) {
        if ($host === $pattern || str_ends_with($host, '.' . $pattern)) {
            return true;
        }
    }

    return false;
}

function job_schema_direct_apply(?string $applyUrl): bool
{
    $normalized = $applyUrl !== null && trim($applyUrl) !== '' ? normalize_apply_url($applyUrl) : null;
    if ($normalized === null) {
        return false;
    }

    if (is_apply_email($normalized)) {
        return true;
    }

    return !is_intermediary_apply_url($normalized);
}

/** @return array<string, mixed> */
function build_job_posting_schema(array $job): array
{
    $address = [
        '@type' => 'PostalAddress',
        'addressLocality' => $job['city_name'],
        'addressRegion' => 'RJ',
        'addressCountry' => 'Brazil',
    ];

    $postalCode = city_postal_code((string) ($job['city_name'] ?? ''));
    if ($postalCode !== null) {
        $address['postalCode'] = $postalCode;
    }

    $hiringOrganization = [
        '@type' => 'Organization',
        'name' => $job['company_name'],
        'logo' => job_hiring_organization_logo($job),
    ];

    $companyWebsite = trim((string) ($job['company_website'] ?? ''));
    if ($companyWebsite !== '') {
        $hiringOrganization['sameAs'] = $companyWebsite;
    }

    $schema = [
        '@context' => 'https://schema.org',
        '@type' => 'JobPosting',
        'title' => $job['title'],
        'description' => html_to_plain_text((string) $job['description']),
        'datePosted' => job_schema_date_posted((string) $job['published_at']),
        'validThrough' => job_schema_valid_through($job['valid_through'] ?? null, (string) $job['published_at']),
        'directApply' => job_schema_direct_apply(isset($job['apply_url']) ? (string) $job['apply_url'] : null),
        'hiringOrganization' => $hiringOrganization,
        'jobLocation' => [
            '@type' => 'Place',
            'address' => $address,
        ],
        'identifier' => [
            '@type' => 'PropertyValue',
            'name' => config('site.name'),
            'value' => 'job-' . $job['id'],
        ],
        'url' => base_url('/vagas/' . $job['slug']),
    ];

    $employmentType = normalize_employment_type(isset($job['employment_type']) ? (string) $job['employment_type'] : null);
    if ($employmentType !== null) {
        $schema['employmentType'] = $employmentType;
    }

    $salaryAmount = parse_job_salary_amount(isset($job['salary']) ? (string) $job['salary'] : null);
    if ($salaryAmount !== null) {
        $schema['baseSalary'] = [
            '@type' => 'MonetaryAmount',
            'currency' => 'BRL',
            'value' => [
                '@type' => 'QuantitativeValue',
                'value' => $salaryAmount,
                'unitText' => 'MONTH',
            ],
        ];
    }

    return $schema;
}

function encode_json_ld(array $data): string
{
    $json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if ($json === false) {
        return '{}';
    }

    return $json;
}

function format_date_br(?string $stored): string
{
    if ($stored === null || trim($stored) === '') {
        return '';
    }

    try {
        return (new \DateTimeImmutable($stored, site_timezone()))->format('d/m/Y');
    } catch (\Exception) {
        return '';
    }
}

function format_datetime_iso_attr(?string $stored): string
{
    if ($stored === null || trim($stored) === '') {
        return '';
    }

    try {
        return (new \DateTimeImmutable($stored, site_timezone()))->format('c');
    } catch (\Exception) {
        return '';
    }
}

function normalize_apply_url(string $raw): ?string
{
    $raw = trim($raw);
    if ($raw === '') {
        return null;
    }

    if (preg_match('/^mailto:/i', $raw)) {
        $email = trim(substr($raw, 7));
        return filter_var($email, FILTER_VALIDATE_EMAIL) ? 'mailto:' . $email : null;
    }

    if (filter_var($raw, FILTER_VALIDATE_EMAIL)) {
        return 'mailto:' . $raw;
    }

    if (!preg_match('#^https?://#i', $raw)) {
        return null;
    }

    return filter_var($raw, FILTER_VALIDATE_URL) ? $raw : null;
}

function is_apply_email(string $applyUrl): bool
{
    return str_starts_with(strtolower(trim($applyUrl)), 'mailto:');
}

function apply_button_label(string $applyUrl): string
{
    return is_apply_email($applyUrl) ? 'Candidatar-se por e-mail' : 'Candidatar-se agora';
}

/** @return array{href:string,target:?string,rel:string,note:string} */
function apply_button_meta(string $applyUrl): array
{
    if (is_apply_email($applyUrl)) {
        return [
            'href' => $applyUrl,
            'target' => null,
            'rel' => 'nofollow',
            'note' => 'Seu cliente de e-mail será aberto para contato com a empresa.',
        ];
    }

    return [
        'href' => $applyUrl,
        'target' => '_blank',
        'rel' => 'nofollow noopener',
        'note' => 'Você será direcionado ao site oficial da empresa.',
    ];
}

/** @return list<list<string>> */
function import_template_required_rows(): array
{
    return [
        ['title', 'company', 'city', 'state', 'description', 'applyUrl'],
        [
            'Assistente Administrativo',
            'Grupo Horizonte',
            'Rio de Janeiro',
            'RJ',
            '<p>Apoio às rotinas administrativas, organização de documentos e atendimento interno.</p>',
            'https://empresa.com/vaga-assistente',
        ],
        [
            'Auxiliar de Logística',
            'Logística Rio',
            'Duque de Caxias',
            'RJ',
            '<p>Apoio à separação, conferência e movimentação de mercadorias no centro de distribuição.</p>',
            'rh@empresa.com.br',
        ],
        [
            'Atendente Comercial',
            'Comercial Guanabara',
            'Niterói',
            'RJ',
            '<p>Atendimento a clientes, apresentação de produtos e registro de pedidos.</p>',
            'https://empresa.com/vaga-comercial',
        ],
    ];
}

/** @return list<list<string>> */
function import_template_rows(): array
{
    return [
        ['title', 'company', 'city', 'state', 'description', 'applyUrl', 'category', 'salary', 'employmentType', 'publishedAt', 'validThrough'],
        [
            'Assistente Administrativo',
            'Grupo Horizonte',
            'Rio de Janeiro',
            'RJ',
            '<p>Apoio às rotinas administrativas.</p>',
            'https://empresa.com/vaga',
            'Administrativo',
            '1600',
            'FULL_TIME',
            '2026-06-01',
            '2026-07-01',
        ],
        [
            'Auxiliar de Logística',
            'Logística Rio',
            'Duque de Caxias',
            'RJ',
            '<p>Apoio à separação e movimentação de mercadorias.</p>',
            'rh@empresa.com.br',
            'Logística',
            '1800',
            'FULL_TIME',
            '2026-06-01T08:00:00-03:00',
            '2026-07-01T23:59:59-03:00',
        ],
    ];
}

function output_import_template_csv(): void
{
    output_csv_download(import_template_rows(), 'modelo-importacao-vagas.csv');
}

function output_import_template_required_csv(): void
{
    output_csv_download(import_template_required_rows(), 'modelo-vagas-obrigatorias.csv');
}

/** @param list<list<string>> $rows */
function output_csv_download(array $rows, string $filename): void
{
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    echo "\xEF\xBB\xBF";
    $out = fopen('php://output', 'w');
    if ($out === false) {
        return;
    }
    foreach ($rows as $row) {
        fputcsv($out, $row);
    }
    fclose($out);
}

function output_import_template_xlsx(): void
{
    output_xlsx_download(import_template_rows(), 'modelo-importacao-vagas.xlsx');
}

function output_import_template_required_xlsx(): void
{
    output_xlsx_download(import_template_required_rows(), 'modelo-vagas-obrigatorias.xlsx');
}

/** @param list<list<string>> $rows */
function output_xlsx_download(array $rows, string $filename): void
{
    if (!class_exists(\ZipArchive::class)) {
        http_response_code(500);
        echo 'Extensao zip necessaria para gerar XLSX.';
        return;
    }

    $shared = [];
    $sharedIndex = [];
    $sheetRows = '';
    foreach ($rows as $rowIndex => $row) {
        $sheetRows .= '<row r="' . ($rowIndex + 1) . '">';
        foreach ($row as $colIndex => $value) {
            $cellRef = chr(65 + $colIndex) . ($rowIndex + 1);
            if (!isset($sharedIndex[$value])) {
                $sharedIndex[$value] = count($shared);
                $shared[] = $value;
            }
            $sheetRows .= '<c r="' . $cellRef . '" t="s"><v>' . $sharedIndex[$value] . '</v></c>';
        }
        $sheetRows .= '</row>';
    }

    $sharedXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><sst xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" count="' . count($shared) . '" uniqueCount="' . count($shared) . '">';
    foreach ($shared as $item) {
        $sharedXml .= '<si><t>' . htmlspecialchars($item, ENT_XML1 | ENT_QUOTES, 'UTF-8') . '</t></si>';
    }
    $sharedXml .= '</sst>';

    $sheetXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
        . '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><sheetData>'
        . $sheetRows
        . '</sheetData></worksheet>';

    $tmp = tempnam(sys_get_temp_dir(), 'modelo-vagas-');
    if ($tmp === false) {
        http_response_code(500);
        echo 'Nao foi possivel gerar o arquivo XLSX.';
        return;
    }

    $zip = new \ZipArchive();
    $zip->open($tmp, \ZipArchive::OVERWRITE);
    $zip->addFromString('[Content_Types].xml', '<?xml version="1.0" encoding="UTF-8"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/><Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/><Override PartName="/xl/sharedStrings.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sharedStrings+xml"/></Types>');
    $zip->addFromString('_rels/.rels', '<?xml version="1.0" encoding="UTF-8"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/></Relationships>');
    $zip->addFromString('xl/_rels/workbook.xml.rels', '<?xml version="1.0" encoding="UTF-8"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/><Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/sharedStrings" Target="sharedStrings.xml"/></Relationships>');
    $zip->addFromString('xl/workbook.xml', '<?xml version="1.0" encoding="UTF-8"?><workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheets><sheet name="Vagas" sheetId="1" r:id="rId1"/></sheets></workbook>');
    $zip->addFromString('xl/worksheets/sheet1.xml', $sheetXml);
    $zip->addFromString('xl/sharedStrings.xml', $sharedXml);
    $zip->close();

    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    readfile($tmp);
    @unlink($tmp);
}

function merge_import_description(array $record): string
{
    $description = clean_html((string) ($record['description'] ?? ''));
    $legacy = [
        'requirements' => 'Requisitos',
        'activities' => 'Atividades',
        'benefits' => 'Benefícios',
        'additionalInfo' => 'Informações adicionais',
        'bonus' => 'Bonificação',
    ];
    $parts = [$description];
    foreach ($legacy as $field => $label) {
        if (!empty($record[$field])) {
            $chunk = clean_html((string) $record[$field]);
            if ($chunk !== '') {
                $parts[] = '<h3>' . e($label) . '</h3>' . $chunk;
            }
        }
    }
    return trim(implode('', array_filter($parts)));
}

function is_admin_logged(): bool
{
    return !empty($_SESSION['admin_logged']) && $_SESSION['admin_logged'] === true;
}

function require_admin(): void
{
    if (!is_admin_logged()) {
        redirect('/admin/login');
    }
}

function admin_login_ok(string $username, string $password): bool
{
    if ($username !== (string) config('admin.username')) {
        return false;
    }
    $hash = (string) config('admin.password_hash', '');
    if ($hash !== '') {
        return password_verify($password, $hash);
    }
    return hash_equals((string) config('admin.password'), $password);
}

function is_ads_enabled(): bool
{
    if (!empty($GLOBALS['__disable_ads']) || !config('ads.enabled', false)) {
        return false;
    }

    return true;
}

function ads_is_configured(): bool
{
    $client = ads_client_id();
    if ($client === '') {
        return false;
    }

    return !preg_match('/X{4,}/i', $client);
}

function ads_slot_is_configured(string $slot): bool
{
    $slot = trim($slot);
    if ($slot === '') {
        return false;
    }

    return !preg_match('/^1{6,}$/', $slot);
}

function is_ads_page_enabled(string $pageType): bool
{
    if (!is_ads_enabled()) {
        return false;
    }

    if (in_array($pageType, ['admin', 'error', 'login'], true)) {
        return false;
    }

    return (bool) config('ads.enabled_pages.' . $pageType, false);
}

function ads_client_id(): string
{
    return trim((string) config('ads.adsense_client_id', ''));
}

function should_show_ad_placeholders(): bool
{
    return is_ads_enabled()
        && config('env') === 'development'
        && (bool) config('ads.show_placeholders_in_dev', false);
}

function mark_ads_used(): void
{
    $GLOBALS['__ads_used'] = true;
}

function ads_used_on_page(): bool
{
    return !empty($GLOBALS['__ads_used']);
}

function ad_slot(
    string $slotConfigKey,
    string $pageType,
    int $width = 970,
    int $height = 250,
    string $extraClass = ''
): string {
    if (!is_ads_page_enabled($pageType)) {
        return '';
    }

    $slot = trim((string) config('ads.slots.' . $slotConfigKey, ''));
    $client = ads_client_id();
    $style = 'style="display:block;min-height:' . $height . 'px;max-width:' . $width . 'px;width:100%;"';
    $containerClass = trim('ad-slot ad-slot-' . $slotConfigKey . ' ' . $extraClass);
    $reservedStyle = 'min-height:' . $height . 'px;max-width:' . $width . 'px;width:100%;margin-inline:auto';

    if (!ads_slot_is_configured($slot) || !ads_is_configured()) {
        if (should_show_ad_placeholders()) {
            return '<aside class="' . e($containerClass) . '" data-ad-reserved="1" aria-label="Espaço reservado para anúncio" style="' . e($reservedStyle) . '">'
                . '<div class="ad-placeholder">Espaço reservado para anúncio</div></aside>';
        }

        return '';
    }

    mark_ads_used();

    return '<aside class="' . e($containerClass) . '" data-ad-reserved="1" aria-label="Publicidade" style="' . e($reservedStyle) . '">'
        . '<ins class="adsbygoogle" ' . $style
        . ' data-ad-client="' . e($client) . '" data-ad-slot="' . e($slot) . '" data-ad-format="auto" data-full-width-responsive="true"></ins>'
        . '</aside>';
}

function render_ads_bootstrap(): string
{
    if (!is_ads_enabled() || !ads_used_on_page() || !ads_is_configured() || should_show_ad_placeholders()) {
        return '';
    }

    $client = ads_client_id();
    $scriptSrc = 'https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=' . rawurlencode($client);

    return '<script async src="' . e($scriptSrc) . '" crossorigin="anonymous"></script>';
}
