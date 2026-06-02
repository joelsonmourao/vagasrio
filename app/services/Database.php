<?php

declare(strict_types=1);

namespace App\Services;

use PDO;

class Database
{
    private static ?PDO $pdo = null;

    public static function init(string $dbPath, array $config): void
    {
        if (!is_dir(dirname($dbPath))) {
            mkdir(dirname($dbPath), 0775, true);
        }

        self::$pdo = new PDO('sqlite:' . $dbPath);
        self::$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        self::$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

        self::migrate();
        DemoDataReset::purgeInvalidData(self::$pdo);
        DemoDataReset::seedIfEmpty(self::$pdo);
        BlogContentSeed::ensureTables(self::$pdo);
        BlogContentSeed::seedIfNeeded(self::$pdo);
    }

    public static function pdo(): PDO
    {
        if (!self::$pdo) {
            throw new \RuntimeException('Banco nao inicializado.');
        }
        return self::$pdo;
    }

    private static function migrate(): void
    {
        $sql = <<<SQL
        CREATE TABLE IF NOT EXISTS companies (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL UNIQUE,
            slug TEXT NOT NULL UNIQUE,
            website TEXT DEFAULT NULL,
            description TEXT DEFAULT NULL,
            created_at TEXT NOT NULL
        );

        CREATE TABLE IF NOT EXISTS categories (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL UNIQUE,
            slug TEXT NOT NULL UNIQUE,
            created_at TEXT NOT NULL
        );

        CREATE TABLE IF NOT EXISTS cities (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL,
            slug TEXT NOT NULL UNIQUE,
            state TEXT NOT NULL,
            created_at TEXT NOT NULL
        );

        CREATE TABLE IF NOT EXISTS jobs (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            title TEXT NOT NULL,
            slug TEXT NOT NULL UNIQUE,
            company_id INTEGER NOT NULL,
            category_id INTEGER DEFAULT NULL,
            city_id INTEGER NOT NULL,
            state TEXT NOT NULL,
            description TEXT NOT NULL,
            requirements TEXT DEFAULT NULL,
            activities TEXT DEFAULT NULL,
            benefits TEXT DEFAULT NULL,
            additional_info TEXT DEFAULT NULL,
            salary TEXT DEFAULT NULL,
            employment_type TEXT DEFAULT NULL,
            apply_url TEXT DEFAULT NULL,
            is_active INTEGER NOT NULL DEFAULT 1,
            published_at TEXT NOT NULL,
            valid_through TEXT DEFAULT NULL,
            source TEXT DEFAULT NULL,
            is_demo INTEGER NOT NULL DEFAULT 0,
            created_at TEXT NOT NULL,
            updated_at TEXT NOT NULL,
            FOREIGN KEY (company_id) REFERENCES companies(id),
            FOREIGN KEY (category_id) REFERENCES categories(id),
            FOREIGN KEY (city_id) REFERENCES cities(id)
        );

        CREATE INDEX IF NOT EXISTS idx_jobs_slug ON jobs(slug);
        CREATE INDEX IF NOT EXISTS idx_jobs_city ON jobs(city_id);
        CREATE INDEX IF NOT EXISTS idx_jobs_state ON jobs(state);
        CREATE INDEX IF NOT EXISTS idx_jobs_company ON jobs(company_id);
        CREATE INDEX IF NOT EXISTS idx_jobs_category ON jobs(category_id);
        CREATE INDEX IF NOT EXISTS idx_jobs_is_active ON jobs(is_active);
        CREATE INDEX IF NOT EXISTS idx_jobs_published_at ON jobs(published_at DESC);
        CREATE INDEX IF NOT EXISTS idx_jobs_active_date ON jobs(is_active, published_at DESC);

        CREATE TABLE IF NOT EXISTS articles (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            title TEXT NOT NULL,
            slug TEXT NOT NULL UNIQUE,
            excerpt TEXT NOT NULL,
            content TEXT NOT NULL,
            published_at TEXT NOT NULL,
            status TEXT NOT NULL DEFAULT 'published'
        );

        CREATE TABLE IF NOT EXISTS imports (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            filename TEXT NOT NULL,
            total_rows INTEGER NOT NULL DEFAULT 0,
            imported_rows INTEGER NOT NULL DEFAULT 0,
            ignored_rows INTEGER NOT NULL DEFAULT 0,
            error_rows INTEGER NOT NULL DEFAULT 0,
            summary_json TEXT DEFAULT NULL,
            created_at TEXT NOT NULL
        );

        CREATE TABLE IF NOT EXISTS import_errors (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            import_id INTEGER NOT NULL,
            row_number INTEGER NOT NULL,
            reason TEXT NOT NULL,
            raw_data TEXT DEFAULT NULL,
            FOREIGN KEY (import_id) REFERENCES imports(id)
        );
        SQL;

        self::$pdo?->exec($sql);
        self::ensureCompanyLogoColumn();
    }

    private static function ensureCompanyLogoColumn(): void
    {
        $columns = self::$pdo?->query('PRAGMA table_info(companies)')->fetchAll() ?: [];
        $hasLogo = false;
        foreach ($columns as $column) {
            if (($column['name'] ?? '') === 'logo') {
                $hasLogo = true;
                break;
            }
        }

        if (!$hasLogo) {
            self::$pdo?->exec('ALTER TABLE companies ADD COLUMN logo TEXT DEFAULT NULL');
        }
    }
}
