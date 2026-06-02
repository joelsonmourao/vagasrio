<?php

declare(strict_types=1);

namespace App\Services;

use PDO;

final class BlogContentSeed
{
    private const EXPECTED_POST_COUNT = 121;

    /** @return list<string> */
    private static function defaultJobCategoryNames(): array
    {
        return [
            'Administrativo',
            'Comercial',
            'Logística',
            'Tecnologia',
            'Financeiro',
            'Atendimento',
            'Operacional',
            'Estágio',
            'Jovem Aprendiz',
            'Recursos Humanos',
            'Vendas',
        ];
    }

    public static function ensureTables(PDO $pdo): void
    {
        $sql = <<<SQL
        CREATE TABLE IF NOT EXISTS blog_categories (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL,
            slug TEXT NOT NULL UNIQUE,
            description TEXT NOT NULL,
            is_active INTEGER NOT NULL DEFAULT 1,
            created_at TEXT NOT NULL,
            updated_at TEXT NOT NULL
        );

        CREATE TABLE IF NOT EXISTS blog_posts (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            category_id INTEGER NOT NULL,
            title TEXT NOT NULL,
            slug TEXT NOT NULL UNIQUE,
            excerpt TEXT NOT NULL,
            content TEXT NOT NULL,
            seo_title TEXT NOT NULL,
            seo_description TEXT NOT NULL,
            published_at TEXT NOT NULL,
            is_active INTEGER NOT NULL DEFAULT 1,
            created_at TEXT NOT NULL,
            updated_at TEXT NOT NULL,
            FOREIGN KEY (category_id) REFERENCES blog_categories(id)
        );

        CREATE INDEX IF NOT EXISTS idx_blog_posts_category ON blog_posts(category_id);
        CREATE INDEX IF NOT EXISTS idx_blog_posts_slug ON blog_posts(slug);
        CREATE INDEX IF NOT EXISTS idx_blog_posts_published ON blog_posts(published_at DESC);
        SQL;

        $pdo->exec($sql);
    }

    public static function ensureDefaultJobCategories(PDO $pdo): int
    {
        $now = date('c');
        $inserted = 0;

        foreach (self::defaultJobCategoryNames() as $name) {
            $check = $pdo->prepare('SELECT id FROM categories WHERE name = ? LIMIT 1');
            $check->execute([$name]);
            if ((int) $check->fetchColumn() > 0) {
                continue;
            }

            $pdo->prepare('INSERT INTO categories (name, slug, created_at) VALUES (?, ?, ?)')
                ->execute([$name, slugify($name), $now]);
            $inserted++;
        }

        return $inserted;
    }

    /**
     * @return array{
     *   seeded: bool,
     *   categories: int,
     *   posts: int,
     *   job_categories_inserted: int,
     *   message: string
     * }
     */
    public static function seedIfNeeded(PDO $pdo): array
    {
        self::ensureTables($pdo);
        $jobCategoriesInserted = self::ensureDefaultJobCategories($pdo);

        $count = (int) $pdo->query('SELECT COUNT(*) FROM blog_posts')->fetchColumn();
        if ($count >= self::EXPECTED_POST_COUNT) {
            return [
                'seeded' => false,
                'categories' => (int) $pdo->query('SELECT COUNT(*) FROM blog_categories')->fetchColumn(),
                'posts' => $count,
                'job_categories_inserted' => $jobCategoriesInserted,
                'message' => 'Blog já possui ' . $count . ' artigos. Nenhum seed necessário.',
            ];
        }

        $result = self::seed($pdo, false);
        $result['job_categories_inserted'] = $jobCategoriesInserted;

        return $result;
    }

    /**
     * @return array{
     *   seeded: bool,
     *   categories: int,
     *   posts: int,
     *   job_categories_inserted: int,
     *   message: string
     * }
     */
    public static function seed(PDO $pdo, bool $force = false): array
    {
        self::ensureTables($pdo);
        $jobCategoriesInserted = self::ensureDefaultJobCategories($pdo);

        if ($force) {
            $pdo->exec('DELETE FROM blog_posts');
        }

        $topics = self::loadTopics();
        $now = date('c');
        $categoriesUpserted = 0;
        $postsInserted = 0;
        $postIndex = 0;

        $upsertCategory = $pdo->prepare(
            'INSERT INTO blog_categories (name, slug, description, is_active, created_at, updated_at)
             VALUES (?, ?, ?, 1, ?, ?)
             ON CONFLICT(slug) DO UPDATE SET
                name = excluded.name,
                description = excluded.description,
                is_active = 1,
                updated_at = excluded.updated_at'
        );

        $findCategory = $pdo->prepare('SELECT id FROM blog_categories WHERE slug = ? LIMIT 1');
        $findPost = $pdo->prepare('SELECT id FROM blog_posts WHERE slug = ? LIMIT 1');
        $insertPost = $pdo->prepare(
            'INSERT INTO blog_posts (
                category_id, title, slug, excerpt, content,
                seo_title, seo_description, published_at, is_active, created_at, updated_at
             ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1, ?, ?)'
        );

        foreach ($topics as $topic) {
            $name = (string) ($topic['name'] ?? '');
            $slug = (string) ($topic['slug'] ?? '');
            $description = (string) ($topic['description'] ?? '');
            $titles = $topic['article_titles'] ?? [];

            if ($name === '' || $slug === '' || !is_array($titles)) {
                continue;
            }

            $upsertCategory->execute([$name, $slug, $description, $now, $now]);
            $categoriesUpserted++;

            $findCategory->execute([$slug]);
            $categoryId = (int) $findCategory->fetchColumn();
            if ($categoryId <= 0) {
                continue;
            }

            foreach ($titles as $title) {
                if (!is_string($title) || trim($title) === '') {
                    continue;
                }

                $postSlug = self::uniquePostSlug($pdo, slugify($title));
                if (!$force) {
                    $findPost->execute([$postSlug]);
                    if ((int) $findPost->fetchColumn() > 0) {
                        continue;
                    }
                }

                $options = [];
                if ($slug === 'vagas-por-cidade') {
                    $city = self::resolveCityForTitle($title);
                    if ($city !== null) {
                        $options['city'] = $city;
                    }
                }

                $built = BlogArticleBuilder::build($title, $name, $slug, $options);
                $publishedAt = self::publishedAtForIndex($postIndex, $now);
                $postIndex++;

                $insertPost->execute([
                    $categoryId,
                    $title,
                    $postSlug,
                    $built['excerpt'],
                    $built['content'],
                    $built['seo_title'],
                    $built['seo_description'],
                    $publishedAt,
                    $now,
                    $now,
                ]);
                $postsInserted++;
            }
        }

        $totalPosts = (int) $pdo->query('SELECT COUNT(*) FROM blog_posts')->fetchColumn();
        $totalCategories = (int) $pdo->query('SELECT COUNT(*) FROM blog_categories')->fetchColumn();

        return [
            'seeded' => true,
            'categories' => $totalCategories,
            'posts' => $totalPosts,
            'job_categories_inserted' => $jobCategoriesInserted,
            'message' => 'Seed concluído: ' . $categoriesUpserted . ' categorias processadas, '
                . $postsInserted . ' artigos inseridos nesta execução (total: ' . $totalPosts . ').',
        ];
    }

    /** @return list<array<string, mixed>> */
    private static function loadTopics(): array
    {
        $path = ROOT_PATH . '/app/content/blog/topics.php';
        if (!is_file($path)) {
            return [];
        }

        $topics = require $path;

        return is_array($topics) ? $topics : [];
    }

    private static function uniquePostSlug(PDO $pdo, string $baseSlug): string
    {
        $slug = $baseSlug !== '' ? $baseSlug : 'artigo';
        $candidate = $slug;
        $suffix = 2;

        $stmt = $pdo->prepare('SELECT COUNT(*) FROM blog_posts WHERE slug = ?');

        while (true) {
            $stmt->execute([$candidate]);
            if ((int) $stmt->fetchColumn() === 0) {
                return $candidate;
            }
            $candidate = $slug . '-' . $suffix;
            $suffix++;
        }
    }

    private static function publishedAtForIndex(int $index, string $now): string
    {
        $daysAgo = min(365, $index);
        $timestamp = strtotime($now . ' -' . $daysAgo . ' days');
        if ($timestamp === false) {
            return $now;
        }

        return date('c', $timestamp);
    }

    private static function resolveCityForTitle(string $title): ?string
    {
        $cities = config('site.allowed_cities', []);
        if (!is_array($cities)) {
            return null;
        }

        foreach ($cities as $city) {
            if (is_string($city) && stripos($title, $city) !== false) {
                return $city;
            }
        }

        return null;
    }
}
