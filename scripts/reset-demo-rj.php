<?php

declare(strict_types=1);

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
