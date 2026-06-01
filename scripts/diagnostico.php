<?php

declare(strict_types=1);

define('ROOT_PATH', dirname(__DIR__));
$config = require ROOT_PATH . '/config/app.php';

$checks = [];

$checks[] = ['PHP >= 8.1', version_compare(PHP_VERSION, '8.1.0', '>='), PHP_VERSION];
$checks[] = ['ext-pdo', extension_loaded('pdo'), extension_loaded('pdo') ? 'ok' : 'faltando'];
$checks[] = ['ext-pdo_sqlite', extension_loaded('pdo_sqlite'), extension_loaded('pdo_sqlite') ? 'ok' : 'faltando'];
$checks[] = ['ext-simplexml', extension_loaded('simplexml'), extension_loaded('simplexml') ? 'ok' : 'faltando'];
$checks[] = ['ext-zip (XLSX)', extension_loaded('zip'), extension_loaded('zip') ? 'ok' : 'faltando (import .xlsx indisponivel)'];
$checks[] = ['database dir gravavel', is_writable(ROOT_PATH . '/database'), is_writable(ROOT_PATH . '/database') ? 'ok' : 'sem permissao'];
$checks[] = ['storage dir gravavel', is_writable(ROOT_PATH . '/storage'), is_writable(ROOT_PATH . '/storage') ? 'ok' : 'sem permissao'];
$checks[] = ['public/.htaccess', is_file(ROOT_PATH . '/public/.htaccess'), is_file(ROOT_PATH . '/public/.htaccess') ? 'ok' : 'faltando'];
$checks[] = ['ads.txt', is_file(ROOT_PATH . '/public/ads.txt'), is_file(ROOT_PATH . '/public/ads.txt') ? 'ok' : 'faltando'];
$checks[] = ['admin credencial padrao', !((string) ($config['admin']['username'] ?? '') === 'admin' && ((string) ($config['admin']['password'] ?? '') === 'admin123') && empty($config['admin']['password_hash'])), 'troque para producao'];

$okCount = 0;
foreach ($checks as $check) {
    if ($check[1]) {
        $okCount++;
    }
}

echo "Diagnostico Portal_Vagas_UF\n";
echo "===========================\n";
foreach ($checks as [$name, $ok, $detail]) {
    echo ($ok ? '[OK] ' : '[ERRO] ') . $name . ' - ' . $detail . PHP_EOL;
}
echo "---------------------------\n";
echo "Resultado: {$okCount}/" . count($checks) . " checks ok\n";

if ($okCount < count($checks)) {
    exit(1);
}
