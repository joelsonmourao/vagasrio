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
