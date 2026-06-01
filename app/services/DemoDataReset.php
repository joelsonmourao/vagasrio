<?php

declare(strict_types=1);

namespace App\Services;

use PDO;

final class DemoDataReset
{
    /** @return list<string> */
    public static function spLegacyCityNames(): array
    {
        return [
            'São Paulo',
            'Campinas',
            'Santos',
            'São José dos Campos',
            'Sorocaba',
            'Guarulhos',
            'Ribeirão Preto',
            'Osasco',
        ];
    }

    /** @return list<string> */
    public static function spLegacyCompanyNames(): array
    {
        return [
            'Tech Paulista',
            'Tech Carioca',
            'Log SP',
            'Log RJ',
            'Grupo Fluminense',
            'Varejo Zona Sul',
            'Portal de Vagas SP',
        ];
    }

    /** @return list<string> */
    public static function demoCompanyNames(): array
    {
        return [
            'Grupo Horizonte',
            'Logística Rio',
            'Varejo Carioca',
            'Serviços RJ',
            'Comercial Guanabara',
        ];
    }

    public static function purgeInvalidData(PDO $pdo): array
    {
        $uf = (string) config('site.main_uf');
        $stats = [
            'jobs_removed' => 0,
            'cities_removed' => 0,
            'companies_removed' => 0,
        ];

        $invalidCityIds = [];
        $allCities = $pdo->query('SELECT id, name, state FROM cities')->fetchAll();
        foreach ($allCities as $city) {
            $name = (string) $city['name'];
            if ((string) $city['state'] !== $uf || !is_allowed_rj_city($name) || in_array($name, self::spLegacyCityNames(), true)) {
                $invalidCityIds[] = (int) $city['id'];
            }
        }

        if ($invalidCityIds !== []) {
            $ids = implode(',', $invalidCityIds);
            $stats['jobs_removed'] += (int) $pdo->exec("DELETE FROM jobs WHERE city_id IN ({$ids})");
            $stats['cities_removed'] += (int) $pdo->exec("DELETE FROM cities WHERE id IN ({$ids})");
        }

        $stats['jobs_removed'] += (int) $pdo->exec('DELETE FROM jobs WHERE state != ' . $pdo->quote($uf));

        foreach (self::spLegacyCompanyNames() as $legacyName) {
            $stmt = $pdo->prepare('SELECT id FROM companies WHERE name = ? LIMIT 1');
            $stmt->execute([$legacyName]);
            $companyId = (int) $stmt->fetchColumn();
            if ($companyId < 1) {
                continue;
            }
            $jobCount = $pdo->prepare('SELECT COUNT(*) FROM jobs WHERE company_id = ?');
            $jobCount->execute([$companyId]);
            if ((int) $jobCount->fetchColumn() === 0) {
                $pdo->prepare('DELETE FROM companies WHERE id = ?')->execute([$companyId]);
                $stats['companies_removed']++;
            }
        }

        self::normalizeCategories($pdo);
        self::ensureAllowedCities($pdo, $uf);

        return $stats;
    }

    public static function normalizeCategories(PDO $pdo): void
    {
        $aliases = [
            'Logistica' => 'Logística',
            'LOGISTICA' => 'Logística',
            'LOGÍSTICA' => 'Logística',
        ];

        foreach ($aliases as $oldName => $newName) {
            $stmt = $pdo->prepare('SELECT id FROM categories WHERE name = ? LIMIT 1');
            $stmt->execute([$oldName]);
            $oldId = (int) $stmt->fetchColumn();
            if ($oldId < 1) {
                continue;
            }

            $stmt = $pdo->prepare('SELECT id FROM categories WHERE name = ? LIMIT 1');
            $stmt->execute([$newName]);
            $newId = (int) $stmt->fetchColumn();

            if ($newId > 0 && $newId !== $oldId) {
                $pdo->prepare('UPDATE jobs SET category_id = ? WHERE category_id = ?')->execute([$newId, $oldId]);
                $pdo->prepare('DELETE FROM categories WHERE id = ?')->execute([$oldId]);
            } else {
                $pdo->prepare('UPDATE categories SET name = ?, slug = ? WHERE id = ?')
                    ->execute([$newName, slugify($newName), $oldId]);
            }
        }
    }

    public static function resetDemoData(PDO $pdo): array
    {
        $uf = (string) config('site.main_uf');
        $now = date('c');

        $pdo->exec('DELETE FROM jobs WHERE is_demo = 1');

        $purge = self::purgeInvalidData($pdo);

        foreach (self::spLegacyCompanyNames() as $legacyName) {
            $stmt = $pdo->prepare('SELECT id FROM companies WHERE name = ?');
            $stmt->execute([$legacyName]);
            while ($row = $stmt->fetch()) {
                $pdo->prepare('DELETE FROM jobs WHERE company_id = ? AND is_demo = 1')->execute([(int) $row['id']]);
                $check = $pdo->prepare('SELECT COUNT(*) FROM jobs WHERE company_id = ?');
                $check->execute([(int) $row['id']]);
                if ((int) $check->fetchColumn() === 0) {
                    $pdo->prepare('DELETE FROM companies WHERE id = ?')->execute([(int) $row['id']]);
                    $purge['companies_removed']++;
                }
            }
        }

        $cityIds = self::ensureAllowedCities($pdo, $uf);
        $catIds = self::ensureCategories($pdo, $now);
        $companyIds = self::ensureDemoCompanies($pdo, $now);

        $demoJobs = [
            ['Assistente Administrativo', 'Grupo Horizonte', 'Rio de Janeiro', 'Administrativo', '<p>Apoio às rotinas administrativas, organização de documentos e atendimento interno. Candidate-se pelo link oficial informado pela empresa.</p>'],
            ['Auxiliar de Logística', 'Logística Rio', 'Duque de Caxias', 'Logística', '<p>Apoio à separação, conferência e movimentação de mercadorias no centro de distribuição. Consulte detalhes no link de candidatura.</p>'],
            ['Atendente Comercial', 'Comercial Guanabara', 'Niterói', 'Comercial', '<p>Atendimento a clientes, apresentação de produtos e registro de pedidos. Processo seletivo conduzido pela empresa contratante.</p>'],
            ['Operador de Loja', 'Varejo Carioca', 'São Gonçalo', 'Comercial', '<p>Operação de loja, reposição de produtos e atendimento ao público. Candidate-se apenas pelo canal oficial da vaga.</p>'],
            ['Jovem Aprendiz Administrativo', 'Serviços RJ', 'Nova Iguaçu', 'Administrativo', '<p>Oportunidade de aprendizado em ambiente corporativo com rotinas administrativas supervisionadas. Verifique requisitos no link de candidatura.</p>'],
        ];

        $jobsCreated = 0;
        $insert = $pdo->prepare(
            'INSERT INTO jobs (title, slug, company_id, category_id, city_id, state, description, requirements, activities, benefits, additional_info, salary, employment_type, apply_url, is_active, published_at, valid_through, source, is_demo, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, NULL, NULL, NULL, NULL, NULL, NULL, ?, 1, ?, ?, ?, 1, ?, ?)'
        );

        foreach ($demoJobs as $index => $job) {
            [$title, $company, $city, $category, $description] = $job;
            $publishedAt = date('c', strtotime("-{$index} day"));
            $validThrough = date('c', strtotime($publishedAt . ' +30 days'));
            $slug = slugify($title . '-' . $city . '-' . $uf);
            $baseSlug = $slug;
            $inc = 1;
            while (self::slugExists($pdo, $slug)) {
                $slug = $baseSlug . '-' . $inc++;
            }

            $insert->execute([
                $title,
                $slug,
                $companyIds[$company],
                $catIds[$category] ?? null,
                $cityIds[$city],
                $uf,
                $description,
                'https://example.com/candidatura',
                $publishedAt,
                $validThrough,
                'demo',
                $now,
                $now,
            ]);
            $jobsCreated++;
        }

        self::ensureDemoArticles($pdo);

        return array_merge($purge, [
            'demo_jobs_created' => $jobsCreated,
            'cities_total' => count($cityIds),
            'companies_total' => count($companyIds),
        ]);
    }

    public static function seedIfEmpty(PDO $pdo): void
    {
        $count = (int) $pdo->query('SELECT COUNT(*) FROM jobs')->fetchColumn();
        if ($count > 0) {
            return;
        }
        self::resetDemoData($pdo);
    }

    /** @return array<string, int> */
    private static function ensureAllowedCities(PDO $pdo, string $uf): array
    {
        $cityIds = [];
        $now = date('c');
        foreach (allowed_rj_cities() as $cityName) {
            $canonical = match_allowed_city_name($cityName) ?? smart_title($cityName);
            $stmt = $pdo->prepare('SELECT id, slug FROM cities WHERE name = ? AND state = ? LIMIT 1');
            $stmt->execute([$canonical, $uf]);
            $row = $stmt->fetch();
            if ($row) {
                $expectedSlug = city_slug($canonical);
                if ($row['slug'] !== $expectedSlug) {
                    $pdo->prepare('UPDATE cities SET slug = ? WHERE id = ?')->execute([$expectedSlug, $row['id']]);
                }
                $cityIds[$canonical] = (int) $row['id'];
                continue;
            }
            $insert = $pdo->prepare('INSERT INTO cities (name, slug, state, created_at) VALUES (?, ?, ?, ?)');
            $insert->execute([$canonical, city_slug($canonical), $uf, $now]);
            $cityIds[$canonical] = (int) $pdo->lastInsertId();
        }
        return $cityIds;
    }

    /** @return array<string, int> */
    private static function ensureCategories(PDO $pdo, string $now): array
    {
        $categories = ['Tecnologia', 'Administrativo', 'Comercial', 'Logística', 'Financeiro'];
        $catIds = [];
        foreach ($categories as $category) {
            $stmt = $pdo->prepare('SELECT id FROM categories WHERE name = ? LIMIT 1');
            $stmt->execute([$category]);
            $id = (int) $stmt->fetchColumn();
            if ($id > 0) {
                $catIds[$category] = $id;
                continue;
            }
            $pdo->prepare('INSERT INTO categories (name, slug, created_at) VALUES (?, ?, ?)')
                ->execute([$category, slugify($category), $now]);
            $catIds[$category] = (int) $pdo->lastInsertId();
        }
        return $catIds;
    }

    /** @return array<string, int> */
    private static function ensureDemoCompanies(PDO $pdo, string $now): array
    {
        $descriptions = [
            'Grupo Horizonte' => 'Empresa com oportunidades em diferentes áreas no Rio de Janeiro.',
            'Logística Rio' => 'Operações logísticas e distribuição no estado do RJ.',
            'Varejo Carioca' => 'Varejo com lojas em municípios do Rio de Janeiro.',
            'Serviços RJ' => 'Serviços corporativos em todo o estado do Rio de Janeiro.',
            'Comercial Guanabara' => 'Equipe comercial com vagas na região metropolitana do RJ.',
        ];
        $companyIds = [];
        foreach (self::demoCompanyNames() as $company) {
            $stmt = $pdo->prepare('SELECT id FROM companies WHERE name = ? LIMIT 1');
            $stmt->execute([$company]);
            $id = (int) $stmt->fetchColumn();
            if ($id > 0) {
                $companyIds[$company] = $id;
                continue;
            }
            $pdo->prepare('INSERT INTO companies (name, slug, website, description, created_at) VALUES (?, ?, ?, ?, ?)')
                ->execute([$company, slugify($company), null, $descriptions[$company] ?? '', $now]);
            $companyIds[$company] = (int) $pdo->lastInsertId();
        }
        return $companyIds;
    }

    private static function ensureDemoArticles(PDO $pdo): void
    {
        $articles = [
            ['Como preparar currículo para vagas no Rio de Janeiro', 'curriculo-vagas-rj', 'Guia prático para destacar experiências no mercado fluminense.', '<p>Organize seu currículo com informações objetivas e adapte o conteúdo para cada vaga do RJ.</p>'],
            ['Como encontrar emprego em cidades do RJ', 'emprego-cidades-rj', 'Estratégias para buscar oportunidades em Niterói, Duque de Caxias e São Gonçalo.', '<p>Combine buscas por cidade, categoria e empresa para ampliar oportunidades na sua região.</p>'],
            ['Dicas para se candidatar com segurança', 'candidatura-segura-rj', 'Identifique oportunidades confiáveis e evite golpes no processo seletivo.', '<p>Desconfie de pedidos de pagamento para candidatura. Confirme informações no site oficial da empresa.</p>'],
        ];
        $stmt = $pdo->prepare('INSERT INTO articles (title, slug, excerpt, content, published_at, status) VALUES (?, ?, ?, ?, ?, ?)');
        foreach ($articles as $index => $article) {
            $check = $pdo->prepare('SELECT COUNT(*) FROM articles WHERE slug = ?');
            $check->execute([$article[1]]);
            if ((int) $check->fetchColumn() > 0) {
                continue;
            }
            $stmt->execute([$article[0], $article[1], $article[2], $article[3], date('c', strtotime('-' . $index . ' day')), 'published']);
        }
    }

    private static function slugExists(PDO $pdo, string $slug): bool
    {
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM jobs WHERE slug = ?');
        $stmt->execute([$slug]);
        return (int) $stmt->fetchColumn() > 0;
    }
}
