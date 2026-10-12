<?php

declare(strict_types=1);

/*
 * Protecao obrigatoria: este script altera dados e nunca deve rodar em
 * producao, pela web ou sem uma confirmacao deliberada.
 *
 * Uso permitido apenas em ambiente descartavel:
 * APP_ENV=development RESET_DEMO_CONFIRMATION=RESET-DEMO-RJ php scripts/reset-demo-rj.php
 */
$environment = strtolower(trim((string) getenv('APP_ENV')));
$confirmation = (string) getenv('RESET_DEMO_CONFIRMATION');
$allowedEnvironments = ['development', 'test'];

if (
    PHP_SAPI !== 'cli'
    || !in_array($environment, $allowedEnvironments, true)
    || !hash_equals('RESET-DEMO-RJ', $confirmation)
) {
    fwrite(
        STDERR,
        "Operacao recusada: reset demonstrativo permitido apenas por CLI, em ambiente development/test e com confirmacao explicita.\n"
    );
    exit(1);
}

require dirname(__DIR__) . '/app/bootstrap.php';

use App\Services\DemoDataReset;

echo "Reset de dados demonstrativos - Vagas RJ\n";
echo str_repeat('-', 42) . "\n";

$result = DemoDataReset::resetDemoData(db());

echo "Vagas removidas (limpeza): {$result['jobs_removed']}\n";
echo "Cidades invalidas removidas: {$result['cities_removed']}\n";
echo "Empresas legadas removidas: {$result['companies_removed']}\n";
echo "Vagas demo criadas: {$result['demo_jobs_created']}\n";
echo "Cidades RJ cadastradas: {$result['cities_total']}\n";
echo "Empresas demo cadastradas: {$result['companies_total']}\n";
echo str_repeat('-', 42) . "\n";
echo "Concluido. Acesse o site para validar.\n";
