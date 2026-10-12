<?php

declare(strict_types=1);

const EXPECTED_IDENTITY = [
    'application_uuid' => 'h7szbectndume9kaqbs8rzx6',
    'domain' => 'vagasrj.rio.br',
    'repository' => 'joelsonmourao/vagasrio',
    'branch' => 'main',
];

function error_if(bool $condition, string $message, array &$errors): void
{
    if ($condition) {
        $errors[] = $message;
    }
}

function value_at(array $data, string $path): mixed
{
    $value = $data;
    foreach (explode('.', $path) as $key) {
        if (!is_array($value) || !array_key_exists($key, $value)) {
            return null;
        }
        $value = $value[$key];
    }
    return $value;
}

function valid_date(mixed $value): bool
{
    return is_string($value) && strtotime($value) !== false;
}

function find_secret_material(mixed $value, string $path = '$'): array
{
    $errors = [];
    if (is_array($value)) {
        foreach ($value as $key => $child) {
            $childPath = $path . '.' . $key;
            if (preg_match('/password|passwd|secret|token|database_url|dsn|connection_string/i', (string) $key)) {
                $errors[] = $childPath . ': chave sensível proibida';
            }
            $errors = array_merge($errors, find_secret_material($child, $childPath));
        }
    } elseif (is_string($value)) {
        if (preg_match('#postgres(?:ql)?://#i', $value)
            || str_contains($value, '-----BEGIN PRIVATE KEY-----')
            || preg_match('/gh[pousr]_[A-Za-z0-9_]{20,}/', $value)) {
            $errors[] = $path . ': possível segredo proibido';
        }
    }
    return $errors;
}

function validate_manifest(array $data): array
{
    $errors = find_secret_material($data);
    error_if(value_at($data, 'schema_version') !== 1, 'schema_version deve ser 1', $errors);

    foreach (EXPECTED_IDENTITY as $key => $expected) {
        error_if(value_at($data, 'identity.' . $key) !== $expected, 'identity.' . $key . ' inválido', $errors);
    }

    $backupId = value_at($data, 'backup.backup_id');
    error_if(!is_string($backupId) || preg_match('/^vrj-\d{8}T\d{6}Z$/', $backupId) !== 1, 'backup.backup_id inválido', $errors);
    error_if(!valid_date(value_at($data, 'backup.captured_at')), 'backup.captured_at inválido', $errors);
    error_if(!is_string(value_at($data, 'backup.operator')) || trim((string) value_at($data, 'backup.operator')) === '', 'backup.operator obrigatório', $errors);
    error_if(!is_string(value_at($data, 'backup.authorization_ref')) || trim((string) value_at($data, 'backup.authorization_ref')) === '', 'backup.authorization_ref obrigatório', $errors);
    error_if(preg_match('/^[a-f0-9]{40}$/', (string) value_at($data, 'backup.deployed_commit')) !== 1, 'backup.deployed_commit deve ser SHA completo', $errors);
    error_if(!is_string(value_at($data, 'backup.php_version')), 'backup.php_version obrigatório', $errors);
    error_if(!is_string(value_at($data, 'backup.sqlite_version')), 'backup.sqlite_version obrigatório', $errors);
    error_if(!in_array(strtolower((string) value_at($data, 'backup.journal_mode')), ['delete', 'truncate', 'persist', 'memory', 'wal', 'off'], true), 'backup.journal_mode inválido', $errors);
    error_if(value_at($data, 'backup.online_backup_completed') !== true, 'backup.online_backup_completed deve ser true', $errors);
    error_if(value_at($data, 'backup.quick_check_passed') !== true, 'backup.quick_check_passed deve ser true', $errors);
    error_if(value_at($data, 'backup.foreign_key_check_passed') !== true, 'backup.foreign_key_check_passed deve ser true', $errors);

    foreach (['database', 'storage'] as $artifact) {
        error_if(preg_match('/^[a-f0-9]{64}$/', (string) value_at($data, 'artifacts.' . $artifact . '_sha256')) !== 1, 'artifacts.' . $artifact . '_sha256 inválido', $errors);
        error_if(!is_int(value_at($data, 'artifacts.' . $artifact . '_bytes')) || value_at($data, 'artifacts.' . $artifact . '_bytes') <= 0, 'artifacts.' . $artifact . '_bytes deve ser positivo', $errors);
    }
    error_if(!is_string(value_at($data, 'artifacts.independent_destination')) || trim((string) value_at($data, 'artifacts.independent_destination')) === '', 'artifacts.independent_destination obrigatório', $errors);
    error_if(value_at($data, 'artifacts.destination_checksums_verified') !== true, 'checksums no destino devem estar verificados', $errors);

    error_if(!valid_date(value_at($data, 'write_pause.started_at')), 'write_pause.started_at inválido', $errors);
    error_if(!valid_date(value_at($data, 'write_pause.finished_at')), 'write_pause.finished_at inválido', $errors);
    if (valid_date(value_at($data, 'write_pause.started_at')) && valid_date(value_at($data, 'write_pause.finished_at'))) {
        $pauseStartedAt = strtotime((string) value_at($data, 'write_pause.started_at'));
        $pauseFinishedAt = strtotime((string) value_at($data, 'write_pause.finished_at'));
        error_if($pauseFinishedAt < $pauseStartedAt, 'write_pause tem ordem inválida', $errors);
        if (valid_date(value_at($data, 'backup.captured_at'))) {
            $capturedAtTimestamp = strtotime((string) value_at($data, 'backup.captured_at'));
            error_if(
                $capturedAtTimestamp < $pauseStartedAt || $capturedAtTimestamp > $pauseFinishedAt,
                'backup.captured_at deve estar dentro da pausa de escrita',
                $errors
            );
        }
    }

    foreach ([
        'restore.isolated',
        'restore.database_integrity_passed',
        'restore.foreign_keys_passed',
        'restore.storage_verified',
        'restore.external_integrations_disabled',
        'restore.smoke_tests_passed',
        'rollback.code_tested',
        'rollback.sqlite_tested',
        'rollback.storage_tested',
        'rollback.passed',
    ] as $path) {
        error_if(value_at($data, $path) !== true, $path . ' deve ser true', $errors);
    }
    $capturedAt = value_at($data, 'backup.captured_at');
    $restoreCompletedAt = value_at($data, 'restore.completed_at');
    $rollbackCompletedAt = value_at($data, 'rollback.completed_at');
    error_if(!valid_date($restoreCompletedAt), 'restore.completed_at inválido', $errors);
    error_if(!valid_date($rollbackCompletedAt), 'rollback.completed_at inválido', $errors);
    error_if(preg_match('/^[a-f0-9]{40}$/', (string) value_at($data, 'rollback.previous_commit')) !== 1, 'rollback.previous_commit deve ser SHA completo', $errors);
    if (valid_date($capturedAt) && valid_date($restoreCompletedAt)) {
        error_if(
            strtotime((string) $restoreCompletedAt) <= strtotime((string) $capturedAt),
            'restore.completed_at deve ser posterior ao backup',
            $errors
        );
    }
    if (valid_date($restoreCompletedAt) && valid_date($rollbackCompletedAt)) {
        error_if(
            strtotime((string) $rollbackCompletedAt) <= strtotime((string) $restoreCompletedAt),
            'rollback.completed_at deve ser posterior ao restore',
            $errors
        );
    }


    $expiresAt = value_at($data, 'retention.expires_at');
    error_if(!valid_date($expiresAt), 'retention.expires_at inválido', $errors);
    if (valid_date($capturedAt) && valid_date($expiresAt)) {
        error_if(strtotime((string) $expiresAt) <= strtotime((string) $capturedAt), 'retention.expires_at deve ser posterior ao backup', $errors);
    }

    $decision = value_at($data, 'gate.decision');
    error_if(!in_array($decision, ['blocked', 'open'], true), 'gate.decision deve ser blocked ou open', $errors);
    if ($decision === 'open') {
        $approvedAt = value_at($data, 'gate.approved_at');
        error_if(!is_string(value_at($data, 'gate.approved_by')) || trim((string) value_at($data, 'gate.approved_by')) === '', 'gate.approved_by obrigatório para open', $errors);
        error_if(!valid_date($approvedAt), 'gate.approved_at obrigatório para open', $errors);
        if (valid_date($rollbackCompletedAt) && valid_date($approvedAt)) {
            error_if(
                strtotime((string) $approvedAt) <= strtotime((string) $rollbackCompletedAt),
                'gate.approved_at deve ser posterior ao rollback',
                $errors
            );
        }
    }

    return array_values(array_unique($errors));
}

function valid_fixture(): array
{
    return [
        'schema_version' => 1,
        'identity' => EXPECTED_IDENTITY,
        'backup' => [
            'backup_id' => 'vrj-20261011T180000Z',
            'captured_at' => '2026-10-11T18:00:00Z',
            'operator' => 'automation-vps',
            'authorization_ref' => 'issue-1',
            'deployed_commit' => str_repeat('a', 40),
            'php_version' => '8.2.0',
            'sqlite_version' => '3.40.0',
            'journal_mode' => 'wal',
            'online_backup_completed' => true,
            'quick_check_passed' => true,
            'foreign_key_check_passed' => true,
        ],
        'artifacts' => [
            'database_sha256' => str_repeat('b', 64),
            'database_bytes' => 1024,
            'storage_sha256' => str_repeat('c', 64),
            'storage_bytes' => 2048,
            'independent_destination' => 'offline-backup:vagasrio/vrj-20261011T180000Z',
            'destination_checksums_verified' => true,
        ],
        'write_pause' => ['started_at' => '2026-10-11T18:00:00Z', 'finished_at' => '2026-10-11T18:02:00Z'],
        'restore' => [
            'isolated' => true,
            'completed_at' => '2026-10-11T18:10:00Z',
            'database_integrity_passed' => true,
            'foreign_keys_passed' => true,
            'storage_verified' => true,
            'external_integrations_disabled' => true,
            'smoke_tests_passed' => true,
        ],
        'rollback' => [
            'completed_at' => '2026-10-11T18:20:00Z',
            'previous_commit' => str_repeat('d', 40),
            'code_tested' => true,
            'sqlite_tested' => true,
            'storage_tested' => true,
            'passed' => true,
        ],
        'retention' => ['expires_at' => '2026-11-10T18:00:00Z'],
        'gate' => ['decision' => 'blocked'],
    ];
}

if (($argv[1] ?? null) === '--self-test') {
    $validErrors = validate_manifest(valid_fixture());
    $invalid = valid_fixture();
    $invalid['identity']['application_uuid'] = 'wrong-app';
    $invalid['database_url'] = 'postgresql://user:password@example.invalid/db';
    $invalidErrors = validate_manifest($invalid);
    $invalidBackup = valid_fixture();
    $invalidBackup['backup']['online_backup_completed'] = false;
    $invalidBackup['backup']['quick_check_passed'] = false;
    $invalidBackup['backup']['foreign_key_check_passed'] = false;
    $invalidBackup['backup']['captured_at'] = '2026-10-11T17:59:59Z';
    $invalidBackupErrors = validate_manifest($invalidBackup);
    $expectedBackupErrors = [
        'backup.online_backup_completed deve ser true',
        'backup.quick_check_passed deve ser true',
        'backup.foreign_key_check_passed deve ser true',
        'backup.captured_at deve estar dentro da pausa de escrita',
    ];
    foreach ($expectedBackupErrors as $expectedError) {
        if (!in_array($expectedError, $invalidBackupErrors, true)) {
            fwrite(STDERR, "Backup self-test failed: " . implode('; ', $invalidBackupErrors) . "\n");
            exit(1);
        }
    }

    $invalidChronology = valid_fixture();
    $invalidChronology['restore']['completed_at'] = '2026-10-11T17:59:59Z';
    $invalidChronology['rollback']['completed_at'] = '2026-10-11T17:59:58Z';
    $invalidChronology['gate'] = [
        'decision' => 'open',
        'approved_by' => 'responsavel-operacional',
        'approved_at' => '2026-10-11T17:59:57Z',
    ];
    $chronologyErrors = validate_manifest($invalidChronology);
    $expectedChronologyErrors = [
        'restore.completed_at deve ser posterior ao backup',
        'rollback.completed_at deve ser posterior ao restore',
        'gate.approved_at deve ser posterior ao rollback',
    ];
    foreach ($expectedChronologyErrors as $expectedError) {
        if (!in_array($expectedError, $chronologyErrors, true)) {
            fwrite(STDERR, "Chronology self-test failed: " . implode('; ', $chronologyErrors) . "\n");
            exit(1);
        }
    }

    if ($validErrors !== [] || count($invalidErrors) < 2) {
        fwrite(STDERR, "Self-test failed\n");
        exit(1);
    }
    fwrite(STDOUT, "Recovery evidence validator: self-test passed\n");
    exit(0);
}

$path = $argv[1] ?? null;
if (!is_string($path) || !is_file($path)) {
    fwrite(STDERR, "Uso: php scripts/validate-recovery-evidence.php <manifest.json>\n");
    exit(2);
}
$decoded = json_decode((string) file_get_contents($path), true);
if (!is_array($decoded)) {
    fwrite(STDERR, "Manifesto JSON inválido\n");
    exit(2);
}
$errors = validate_manifest($decoded);
if ($errors !== []) {
    foreach ($errors as $error) {
        fwrite(STDERR, "- " . $error . "\n");
    }
    exit(1);
}
fwrite(STDOUT, "Recovery evidence: VALID\n");
