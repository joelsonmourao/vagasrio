<?php

declare(strict_types=1);

function loadDatabaseConfig(string $appEnv, ?string $allowWrites): array
{
    putenv('APP_ENV=' . $appEnv);
    if ($allowWrites === null) {
        putenv('APP_ALLOW_BOOTSTRAP_WRITES');
    } else {
        putenv('APP_ALLOW_BOOTSTRAP_WRITES=' . $allowWrites);
    }

    return require dirname(__DIR__) . '/config/app.php';
}

$cases = [
    ['production', 'true', false],
    ['production', 'false', false],
    ['production', null, false],
    ['development', 'true', true],
    ['development', 'false', false],
    ['test', 'true', true],
];

foreach ($cases as [$env, $flag, $expected]) {
    $config = loadDatabaseConfig($env, $flag);
    $actual = ($config['database']['allow_bootstrap_writes'] ?? false) === true;
    if ($actual !== $expected) {
        fwrite(STDERR, sprintf(
            "database bootstrap guard failed for APP_ENV=%s APP_ALLOW_BOOTSTRAP_WRITES=%s\n",
            $env,
            $flag ?? '<unset>'
        ));
        exit(1);
    }
}

$databaseSource = file_get_contents(dirname(__DIR__) . '/app/services/Database.php');
if ($databaseSource === false) {
    fwrite(STDERR, "unable to read Database.php\n");
    exit(1);
}

$guardPosition = strpos($databaseSource, 'if (!$allowBootstrapWrites) {');
$migrationPosition = strpos($databaseSource, 'self::migrate();');
$seedPosition = strpos($databaseSource, 'DemoDataReset::seedIfEmpty');
$purgePosition = strpos($databaseSource, 'ExpiredJobsPurge::run');

if ($guardPosition === false || $migrationPosition === false || $seedPosition === false || $purgePosition === false) {
    fwrite(STDERR, "database bootstrap guard or protected operations missing\n");
    exit(1);
}

if ($guardPosition > $migrationPosition || $guardPosition > $seedPosition || $guardPosition > $purgePosition) {
    fwrite(STDERR, "database write operations are reachable before the bootstrap guard\n");
    exit(1);
}

echo "database bootstrap writes are fail-closed in production\n";
