<?php

declare(strict_types=1);

namespace App\Services;

use PDO;

class PortalService
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function homeData(): array
    {
        $uf = config('site.main_uf');
        $activeJobsStmt = $this->pdo->prepare('SELECT COUNT(*) FROM jobs WHERE is_active = 1 AND state = ?');
        $activeJobsStmt->execute([$uf]);
        $activeJobs = (int) $activeJobsStmt->fetchColumn();

        $citiesCountStmt = $this->pdo->prepare(
            'SELECT COUNT(DISTINCT city_id) FROM jobs WHERE is_active = 1 AND state = ?'
        );
        $citiesCountStmt->execute([$uf]);
        $citiesCount = (int) $citiesCountStmt->fetchColumn();

        $companiesCountStmt = $this->pdo->prepare(
            'SELECT COUNT(DISTINCT company_id) FROM jobs WHERE is_active = 1 AND state = ?'
        );
        $companiesCountStmt->execute([$uf]);
        $companiesCount = (int) $companiesCountStmt->fetchColumn();

        return [
            'recentJobs' => $this->jobList(['page' => 1, 'perPage' => 8])['jobs'],
            'cities' => $this->citiesWithStats(),
            'categories' => $this->categories(),
            'recentArticles' => $this->articles(6),
            'stats' => [
                'active_jobs' => $activeJobs,
                'cities' => $citiesCount,
                'companies' => $companiesCount,
            ],
        ];
    }

    public function jobList(array $filters): array
    {
        $perPage = max(1, min(40, (int) ($filters['perPage'] ?? config('jobs.per_page', 12))));
        $page = max(1, (int) ($filters['page'] ?? 1));
        $offset = ($page - 1) * $perPage;
        $includeInactive = (bool) ($filters['includeInactive'] ?? false);

        $where = ['j.state = :state'];
        $params = [':state' => config('site.main_uf')];
        if (!empty($filters['q'])) {
            $where[] = '(j.title LIKE :q OR j.description LIKE :q OR c.name LIKE :q)';
            $params[':q'] = '%' . trim((string) $filters['q']) . '%';
        }
        if (!empty($filters['city'])) {
            $where[] = 'ci.slug = :city_slug';
            $params[':city_slug'] = (string) $filters['city'];
        }
        if (!empty($filters['company'])) {
            $where[] = 'c.slug = :company_slug';
            $params[':company_slug'] = (string) $filters['company'];
        }
        if (!empty($filters['category'])) {
            $where[] = 'ca.slug = :category_slug';
            $params[':category_slug'] = (string) $filters['category'];
        }
        if (!empty($filters['status'])) {
            if ($filters['status'] === 'active') {
                $where[] = 'j.is_active = 1';
            } elseif ($filters['status'] === 'inactive') {
                $where[] = 'j.is_active = 0';
            }
        } elseif (!$includeInactive) {
            $where[] = 'j.is_active = 1';
            $where[] = $this->jobNotExpiredCondition('j');
            $params[':now_valid'] = date('c');
        }

        $whereSql = implode(' AND ', $where);
        $countSql = "SELECT COUNT(*) FROM jobs j
            INNER JOIN companies c ON c.id = j.company_id
            INNER JOIN cities ci ON ci.id = j.city_id
            LEFT JOIN categories ca ON ca.id = j.category_id
            WHERE {$whereSql}";
        $countStmt = $this->pdo->prepare($countSql);
        $countStmt->execute($params);
        $total = (int) $countStmt->fetchColumn();
        $totalPages = max(1, (int) ceil($total / $perPage));
        if ($page > $totalPages && $total > 0) {
            $page = $totalPages;
            $offset = ($page - 1) * $perPage;
        }

        $sql = "SELECT j.*, c.name AS company_name, c.slug AS company_slug, ci.name AS city_name, ci.slug AS city_slug,
                    ca.name AS category_name, ca.slug AS category_slug
                FROM jobs j
                INNER JOIN companies c ON c.id = j.company_id
                INNER JOIN cities ci ON ci.id = j.city_id
                LEFT JOIN categories ca ON ca.id = j.category_id
                WHERE {$whereSql}
                ORDER BY j.published_at DESC
                LIMIT :limit OFFSET :offset";
        $stmt = $this->pdo->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return [
            'jobs' => $stmt->fetchAll(),
            'total' => $total,
            'page' => $page,
            'perPage' => $perPage,
            'totalPages' => $totalPages,
        ];
    }

    public function jobBySlug(string $slug): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT j.*, c.name AS company_name, c.slug AS company_slug, c.website AS company_website,
                c.logo AS company_logo,
                ci.name AS city_name, ci.slug AS city_slug, ca.name AS category_name, ca.slug AS category_slug
             FROM jobs j
             INNER JOIN companies c ON c.id = j.company_id
             INNER JOIN cities ci ON ci.id = j.city_id
             LEFT JOIN categories ca ON ca.id = j.category_id
             WHERE j.slug = :slug
               AND j.state = :state
               AND j.is_active = 1
               AND ' . $this->jobNotExpiredCondition('j') . '
             LIMIT 1'
        );
        $stmt->execute([
            ':slug' => $slug,
            ':state' => config('site.main_uf'),
            ':now_valid' => date('c'),
        ]);
        $job = $stmt->fetch(PDO::FETCH_ASSOC);

        return $job ?: null;
    }

    public function relatedJobs(array $job, int $limit = 4): array
    {
        $categoryId = (int) ($job['category_id'] ?? 0);
        $notExpired = $this->jobNotExpiredCondition('j');
        if ($categoryId > 0) {
            $sql = 'SELECT j.*, c.name AS company_name, ci.name AS city_name, ca.name AS category_name
                 FROM jobs j
                 INNER JOIN companies c ON c.id = j.company_id
                 INNER JOIN cities ci ON ci.id = j.city_id
                 LEFT JOIN categories ca ON ca.id = j.category_id
                 WHERE j.state = :state AND j.is_active = 1 AND j.id != :id
                 AND ' . $notExpired . '
                 AND (j.city_id = :city_id OR j.category_id = :category_id)
                 ORDER BY j.published_at DESC
                 LIMIT :limit';
        } else {
            $sql = 'SELECT j.*, c.name AS company_name, ci.name AS city_name, ca.name AS category_name
                 FROM jobs j
                 INNER JOIN companies c ON c.id = j.company_id
                 INNER JOIN cities ci ON ci.id = j.city_id
                 LEFT JOIN categories ca ON ca.id = j.category_id
                 WHERE j.state = :state AND j.is_active = 1 AND j.id != :id
                 AND ' . $notExpired . '
                 AND j.city_id = :city_id
                 ORDER BY j.published_at DESC
                 LIMIT :limit';
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':state', config('site.main_uf'));
        $stmt->bindValue(':now_valid', date('c'));
        $stmt->bindValue(':id', (int) $job['id'], PDO::PARAM_INT);
        $stmt->bindValue(':city_id', (int) $job['city_id'], PDO::PARAM_INT);
        if ($categoryId > 0) {
            $stmt->bindValue(':category_id', $categoryId, PDO::PARAM_INT);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function cities(): array
    {
        [$inClause, $cityParams] = $this->allowedCityFilter('name');
        $stmt = $this->pdo->prepare("SELECT * FROM cities WHERE state = :state AND {$inClause} ORDER BY name ASC");
        $stmt->execute(array_merge([':state' => config('site.main_uf')], $cityParams));
        return $stmt->fetchAll();
    }

    public function citiesWithStats(): array
    {
        [$inClause, $cityParams] = $this->allowedCityFilter('ci.name');
        $params = array_merge([':state' => config('site.main_uf')], $cityParams);
        $stmt = $this->pdo->prepare(
            "SELECT ci.*, COUNT(j.id) AS jobs_count
             FROM cities ci
             LEFT JOIN jobs j ON j.city_id = ci.id AND j.is_active = 1 AND j.state = :state
             WHERE ci.state = :state AND {$inClause}
             GROUP BY ci.id
             ORDER BY jobs_count DESC, ci.name ASC"
        );
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function cityBySlug(string $slug): ?array
    {
        $uf = config('site.main_uf');
        $candidates = array_values(array_unique(array_filter([
            $slug,
            str_ends_with($slug, '-rj') ? substr($slug, 0, -3) : $slug . '-rj',
            city_slug(str_replace('-', ' ', preg_replace('/-rj$/', '', $slug) ?? $slug)),
        ])));

        foreach ($candidates as $candidate) {
            $stmt = $this->pdo->prepare('SELECT * FROM cities WHERE slug = :slug AND state = :state LIMIT 1');
            $stmt->execute([':slug' => $candidate, ':state' => $uf]);
            $city = $stmt->fetch();
            if ($city) {
                return $city;
            }
        }

        return null;
    }

    public function companies(): array
    {
        return $this->pdo->query('SELECT * FROM companies ORDER BY name ASC')->fetchAll();
    }

    public function companiesWithStats(): array
    {
        return $this->pdo->query(
            'SELECT c.*, COUNT(j.id) AS jobs_count
             FROM companies c
             LEFT JOIN jobs j ON j.company_id = c.id
             GROUP BY c.id
             ORDER BY c.name ASC'
        )->fetchAll();
    }

    public function companyById(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM companies WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);
        $company = $stmt->fetch();
        return $company ?: null;
    }

    public function companyBySlug(string $slug): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM companies WHERE slug = :slug LIMIT 1');
        $stmt->execute([':slug' => $slug]);
        $company = $stmt->fetch();
        return $company ?: null;
    }

    public function categories(): array
    {
        return $this->pdo->query('SELECT * FROM categories ORDER BY name ASC')->fetchAll();
    }

    public function categoriesWithStats(): array
    {
        return $this->pdo->query(
            'SELECT ca.*, COUNT(j.id) AS jobs_count
             FROM categories ca
             LEFT JOIN jobs j ON j.category_id = ca.id
             GROUP BY ca.id
             ORDER BY ca.name ASC'
        )->fetchAll();
    }

    public function categoryById(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM categories WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);
        $category = $stmt->fetch();
        return $category ?: null;
    }

    public function categoryBySlug(string $slug): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM categories WHERE slug = :slug LIMIT 1');
        $stmt->execute([':slug' => $slug]);
        $category = $stmt->fetch();
        return $category ?: null;
    }

    public function articles(int $limit = 0, int $offset = 0, ?string $categorySlug = null, bool $activeOnly = true): array
    {
        $where = $activeOnly ? ['p.is_active = 1'] : ['1=1'];
        $params = [];
        if ($categorySlug !== null && $categorySlug !== '') {
            $where[] = 'c.slug = :category_slug';
            $params[':category_slug'] = $categorySlug;
        }
        $whereSql = implode(' AND ', $where);
        $sql = "SELECT p.*, c.name AS category_name, c.slug AS category_slug
                FROM blog_posts p
                INNER JOIN blog_categories c ON c.id = p.category_id
                WHERE {$whereSql}
                ORDER BY p.published_at DESC";
        if ($limit > 0) {
            $sql .= ' LIMIT :limit OFFSET :offset';
        }
        $stmt = $this->pdo->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        if ($limit > 0) {
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        }
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public function articlesCount(?string $categorySlug = null, string $search = ''): int
    {
        $where = ['p.is_active = 1'];
        $params = [];
        if ($categorySlug !== null && $categorySlug !== '') {
            $where[] = 'c.slug = :category_slug';
            $params[':category_slug'] = $categorySlug;
        }
        if ($search !== '') {
            $where[] = '(p.title LIKE :search OR p.excerpt LIKE :search)';
            $params[':search'] = '%' . $search . '%';
        }
        $whereSql = implode(' AND ', $where);
        $sql = "SELECT COUNT(*) FROM blog_posts p
                INNER JOIN blog_categories c ON c.id = p.category_id
                WHERE {$whereSql}";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return (int) $stmt->fetchColumn();
    }

    public function articleList(array $filters): array
    {
        $perPage = max(1, min(24, (int) ($filters['perPage'] ?? config('blog.per_page', 12))));
        $page = max(1, (int) ($filters['page'] ?? 1));
        $offset = ($page - 1) * $perPage;
        $categorySlug = isset($filters['category']) ? (string) $filters['category'] : null;
        if ($categorySlug === '') {
            $categorySlug = null;
        }
        $search = trim((string) ($filters['q'] ?? ''));
        $activeOnly = !array_key_exists('activeOnly', $filters) || !empty($filters['activeOnly']);

        $where = $activeOnly ? ['p.is_active = 1'] : ['1=1'];
        $params = [];
        if ($categorySlug !== null) {
            $where[] = 'c.slug = :category_slug';
            $params[':category_slug'] = $categorySlug;
        }
        if ($search !== '') {
            $where[] = '(p.title LIKE :search OR p.excerpt LIKE :search)';
            $params[':search'] = '%' . $search . '%';
        }
        $whereSql = implode(' AND ', $where);

        $countSql = "SELECT COUNT(*) FROM blog_posts p
                     INNER JOIN blog_categories c ON c.id = p.category_id
                     WHERE {$whereSql}";
        $countStmt = $this->pdo->prepare($countSql);
        $countStmt->execute($params);
        $total = (int) $countStmt->fetchColumn();
        $totalPages = max(1, (int) ceil($total / $perPage));
        if ($page > $totalPages && $total > 0) {
            $page = $totalPages;
            $offset = ($page - 1) * $perPage;
        }

        $sql = "SELECT p.*, c.name AS category_name, c.slug AS category_slug
                FROM blog_posts p
                INNER JOIN blog_categories c ON c.id = p.category_id
                WHERE {$whereSql}
                ORDER BY p.published_at DESC
                LIMIT :limit OFFSET :offset";
        $stmt = $this->pdo->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return [
            'articles' => $stmt->fetchAll(),
            'total' => $total,
            'page' => $page,
            'perPage' => $perPage,
            'totalPages' => $totalPages,
        ];
    }

    public function articleBySlug(string $slug): ?array
    {
        $stmt = $this->pdo->prepare(
            "SELECT p.*, c.name AS category_name, c.slug AS category_slug
             FROM blog_posts p
             INNER JOIN blog_categories c ON c.id = p.category_id
             WHERE p.slug = :slug AND p.is_active = 1
             LIMIT 1"
        );
        $stmt->execute([':slug' => $slug]);
        $article = $stmt->fetch();

        return $article ?: null;
    }

    public function blogCategories(bool $activeOnly = true): array
    {
        $sql = 'SELECT c.*, COUNT(p.id) AS posts_count
                FROM blog_categories c
                LEFT JOIN blog_posts p ON p.category_id = c.id AND p.is_active = 1';
        if ($activeOnly) {
            $sql .= ' WHERE c.is_active = 1';
        }
        $sql .= ' GROUP BY c.id ORDER BY c.name ASC';

        return $this->pdo->query($sql)->fetchAll();
    }

    public function blogCategoryBySlug(string $slug): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM blog_categories WHERE slug = :slug AND is_active = 1 LIMIT 1');
        $stmt->execute([':slug' => $slug]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    public function blogCategoryById(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM blog_categories WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    public function relatedBlogPosts(?int $categoryId, int $excludeId = 0, int $limit = 4): array
    {
        if ($categoryId === null || $categoryId < 1) {
            return array_slice($this->articles($limit), 0, $limit);
        }
        $stmt = $this->pdo->prepare(
            "SELECT p.*, c.name AS category_name, c.slug AS category_slug
             FROM blog_posts p
             INNER JOIN blog_categories c ON c.id = p.category_id
             WHERE p.is_active = 1 AND p.category_id = :category_id AND p.id != :exclude_id
             ORDER BY p.published_at DESC
             LIMIT :limit"
        );
        $stmt->bindValue(':category_id', $categoryId, PDO::PARAM_INT);
        $stmt->bindValue(':exclude_id', $excludeId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public function blogPostsForCity(string $cityName, int $limit = 4): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT p.*, c.name AS category_name, c.slug AS category_slug
             FROM blog_posts p
             INNER JOIN blog_categories c ON c.id = p.category_id
             WHERE p.is_active = 1 AND c.slug = 'vagas-por-cidade' AND p.title LIKE :city
             ORDER BY p.published_at DESC
             LIMIT :limit"
        );
        $stmt->bindValue(':city', '%' . $cityName . '%');
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        $posts = $stmt->fetchAll();
        if ($posts !== []) {
            return $posts;
        }

        return $this->relatedBlogPosts(null, 0, $limit);
    }

    public function blogPostsForJobCategory(string $categoryName, int $limit = 4): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT p.*, c.name AS category_name, c.slug AS category_slug
             FROM blog_posts p
             INNER JOIN blog_categories c ON c.id = p.category_id
             WHERE p.is_active = 1 AND c.slug = 'profissoes-e-areas' AND p.title LIKE :term
             ORDER BY p.published_at DESC
             LIMIT :limit"
        );
        $stmt->bindValue(':term', '%' . $categoryName . '%');
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        $posts = $stmt->fetchAll();
        if ($posts !== []) {
            return $posts;
        }

        return $this->relatedBlogPosts(null, 0, $limit);
    }

    public function blogPostById(int $id): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT p.*, c.name AS category_name, c.slug AS category_slug
             FROM blog_posts p
             INNER JOIN blog_categories c ON c.id = p.category_id
             WHERE p.id = ? LIMIT 1'
        );
        $stmt->execute([$id]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    public function createBlogCategory(array $payload): void
    {
        $name = smart_title(trim((string) ($payload['name'] ?? '')));
        if ($name === '') {
            throw new \InvalidArgumentException('Nome da categoria do blog é obrigatório.');
        }
        $now = date('c');
        $stmt = $this->pdo->prepare(
            'INSERT INTO blog_categories (name, slug, description, is_active, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $name,
            slugify($name),
            trim((string) ($payload['description'] ?? '')),
            !empty($payload['is_active']) ? 1 : 0,
            $now,
            $now,
        ]);
    }

    public function updateBlogCategory(int $id, array $payload): void
    {
        $category = $this->blogCategoryById($id);
        if (!$category) {
            throw new \InvalidArgumentException('Categoria do blog não encontrada.');
        }
        $name = smart_title(trim((string) ($payload['name'] ?? '')));
        if ($name === '') {
            throw new \InvalidArgumentException('Nome da categoria do blog é obrigatório.');
        }
        $stmt = $this->pdo->prepare(
            'UPDATE blog_categories SET name = ?, slug = ?, description = ?, is_active = ?, updated_at = ? WHERE id = ?'
        );
        $stmt->execute([
            $name,
            slugify($name),
            trim((string) ($payload['description'] ?? '')),
            !empty($payload['is_active']) ? 1 : 0,
            date('c'),
            $id,
        ]);
    }

    public function deleteBlogCategory(int $id): void
    {
        $count = $this->pdo->prepare('SELECT COUNT(*) FROM blog_posts WHERE category_id = ?');
        $count->execute([$id]);
        if ((int) $count->fetchColumn() > 0) {
            throw new \InvalidArgumentException('Não é possível excluir categoria com artigos vinculados.');
        }
        $this->pdo->prepare('DELETE FROM blog_categories WHERE id = ?')->execute([$id]);
    }

    public function saveBlogPost(array $payload, ?int $id = null): void
    {
        $title = trim((string) ($payload['title'] ?? ''));
        if ($title === '') {
            throw new \InvalidArgumentException('Título do artigo é obrigatório.');
        }
        $categoryId = (int) ($payload['category_id'] ?? 0);
        if ($categoryId < 1 || !$this->blogCategoryById($categoryId)) {
            throw new \InvalidArgumentException('Categoria do artigo é obrigatória.');
        }
        $slug = trim((string) ($payload['slug'] ?? ''));
        $slug = $slug !== '' ? slugify($slug) : slugify($title);
        if ($this->blogPostSlugExists($slug, $id)) {
            throw new \InvalidArgumentException('Slug do artigo já existe.');
        }
        $excerpt = trim((string) ($payload['excerpt'] ?? ''));
        $content = clean_html((string) ($payload['content'] ?? ''));
        if ($content === '') {
            throw new \InvalidArgumentException('Conteúdo do artigo é obrigatório.');
        }
        if ($excerpt === '') {
            $excerpt = excerpt(html_to_plain_text($content), 160);
        }
        $seoTitle = trim((string) ($payload['seo_title'] ?? ''));
        if ($seoTitle === '') {
            $seoTitle = $title . ' | Blog Vagas RJ';
        }
        $seoDescription = trim((string) ($payload['seo_description'] ?? ''));
        if ($seoDescription === '') {
            $seoDescription = $excerpt;
        }
        $publishedAt = resolve_job_published_at((string) ($payload['published_at'] ?? ''));
        $isActive = !empty($payload['is_active']) ? 1 : 0;
        $now = date('c');

        if ($id !== null) {
            $stmt = $this->pdo->prepare(
                'UPDATE blog_posts SET category_id = ?, title = ?, slug = ?, excerpt = ?, content = ?, seo_title = ?, seo_description = ?, published_at = ?, is_active = ?, updated_at = ? WHERE id = ?'
            );
            $stmt->execute([$categoryId, $title, $slug, $excerpt, $content, $seoTitle, $seoDescription, $publishedAt, $isActive, $now, $id]);

            return;
        }

        $stmt = $this->pdo->prepare(
            'INSERT INTO blog_posts (category_id, title, slug, excerpt, content, seo_title, seo_description, published_at, is_active, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([$categoryId, $title, $slug, $excerpt, $content, $seoTitle, $seoDescription, $publishedAt, $isActive, $now, $now]);
    }

    public function deleteBlogPost(int $id): void
    {
        $this->pdo->prepare('DELETE FROM blog_posts WHERE id = ?')->execute([$id]);
    }

    public function toggleBlogPost(int $id): void
    {
        $this->pdo->prepare('UPDATE blog_posts SET is_active = CASE WHEN is_active = 1 THEN 0 ELSE 1 END, updated_at = ? WHERE id = ?')
            ->execute([date('c'), $id]);
    }

    public function seedBlogContent(bool $force = false): array
    {
        return BlogContentSeed::seed($this->pdo, $force);
    }

    private function blogPostSlugExists(string $slug, ?int $ignoreId): bool
    {
        if ($ignoreId) {
            $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM blog_posts WHERE slug = ? AND id != ?');
            $stmt->execute([$slug, $ignoreId]);

            return (int) $stmt->fetchColumn() > 0;
        }
        $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM blog_posts WHERE slug = ?');
        $stmt->execute([$slug]);

        return (int) $stmt->fetchColumn() > 0;
    }

    public function dashboardStats(): array
    {
        $today = date('Y-m-d');
        $uf = (string) config('site.main_uf');
        $stmtCities = $this->pdo->prepare('SELECT COUNT(DISTINCT city_id) FROM jobs WHERE state = ?');
        $stmtCities->execute([$uf]);

        return [
            'total' => (int) $this->pdo->query('SELECT COUNT(*) FROM jobs')->fetchColumn(),
            'active' => (int) $this->pdo->query('SELECT COUNT(*) FROM jobs WHERE is_active = 1')->fetchColumn(),
            'inactive' => (int) $this->pdo->query('SELECT COUNT(*) FROM jobs WHERE is_active = 0')->fetchColumn(),
            'today' => (int) $this->pdo->query("SELECT COUNT(*) FROM jobs WHERE date(published_at) = '{$today}'")->fetchColumn(),
            'companies_total' => (int) $this->pdo->query('SELECT COUNT(*) FROM companies')->fetchColumn(),
            'cities_with_jobs' => (int) $stmtCities->fetchColumn(),
            'by_city' => $this->pdo->query(
                "SELECT ci.name, COUNT(*) AS qty FROM jobs j
                 INNER JOIN cities ci ON ci.id = j.city_id
                 GROUP BY ci.name ORDER BY qty DESC LIMIT 8"
            )->fetchAll(),
            'recent_jobs' => $this->pdo->query(
                "SELECT j.id, j.title, j.slug, j.published_at, j.is_active, c.name AS company_name, ci.name AS city_name, ca.name AS category_name
                 FROM jobs j
                 INNER JOIN companies c ON c.id = j.company_id
                 INNER JOIN cities ci ON ci.id = j.city_id
                 LEFT JOIN categories ca ON ca.id = j.category_id
                 ORDER BY j.created_at DESC LIMIT 8"
            )->fetchAll(),
            'recent_imports' => $this->pdo->query('SELECT * FROM imports ORDER BY created_at DESC LIMIT 8')->fetchAll(),
            'import_errors' => $this->pdo->query('SELECT * FROM import_errors ORDER BY id DESC LIMIT 12')->fetchAll(),
        ];
    }

    public function createCompany(array $payload): void
    {
        $name = smart_title((string) ($payload['name'] ?? ''));
        if ($name === '') {
            throw new \InvalidArgumentException('Nome da empresa e obrigatorio.');
        }
        $slug = slugify($name);
        $stmt = $this->pdo->prepare('INSERT INTO companies (name, slug, website, description, created_at) VALUES (?, ?, ?, ?, ?)');
        $stmt->execute([
            $name,
            $slug,
            trim((string) ($payload['website'] ?? '')),
            clean_html((string) ($payload['description'] ?? '')),
            date('c'),
        ]);
    }

    public function updateCompany(int $id, array $payload): void
    {
        $company = $this->companyById($id);
        if (!$company) {
            throw new \InvalidArgumentException('Empresa não encontrada.');
        }
        $name = smart_title((string) ($payload['name'] ?? ''));
        if ($name === '') {
            throw new \InvalidArgumentException('Nome da empresa é obrigatório.');
        }
        $slug = slugify($name);
        $stmt = $this->pdo->prepare('UPDATE companies SET name = ?, slug = ?, website = ?, description = ? WHERE id = ?');
        $stmt->execute([
            $name,
            $slug,
            trim((string) ($payload['website'] ?? '')),
            clean_html((string) ($payload['description'] ?? '')),
            $id,
        ]);
    }

    public function deleteCompany(int $id): void
    {
        $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM jobs WHERE company_id = ?');
        $stmt->execute([$id]);
        if ((int) $stmt->fetchColumn() > 0) {
            throw new \InvalidArgumentException('Não é possível excluir empresa com vagas vinculadas.');
        }
        $this->pdo->prepare('DELETE FROM companies WHERE id = ?')->execute([$id]);
    }

    public function createCategory(array $payload): void
    {
        $name = normalize_category_name((string) ($payload['name'] ?? ''));
        if ($name === '') {
            throw new \InvalidArgumentException('Nome da categoria e obrigatorio.');
        }
        $stmt = $this->pdo->prepare('INSERT INTO categories (name, slug, created_at) VALUES (?, ?, ?)');
        $stmt->execute([$name, slugify($name), date('c')]);
    }

    public function updateCategory(int $id, array $payload): void
    {
        $category = $this->categoryById($id);
        if (!$category) {
            throw new \InvalidArgumentException('Categoria não encontrada.');
        }
        $name = normalize_category_name((string) ($payload['name'] ?? ''));
        if ($name === '') {
            throw new \InvalidArgumentException('Nome da categoria é obrigatório.');
        }
        $stmt = $this->pdo->prepare('UPDATE categories SET name = ?, slug = ? WHERE id = ?');
        $stmt->execute([$name, slugify($name), $id]);
    }

    public function deleteCategory(int $id): void
    {
        $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM jobs WHERE category_id = ?');
        $stmt->execute([$id]);
        if ((int) $stmt->fetchColumn() > 0) {
            throw new \InvalidArgumentException('Não é possível excluir categoria com vagas vinculadas.');
        }
        $this->pdo->prepare('DELETE FROM categories WHERE id = ?')->execute([$id]);
    }

    public function importErrors(int $importId, int $limit = 30): array
    {
        $stmt = $this->pdo->prepare('SELECT row_number, reason, raw_data FROM import_errors WHERE import_id = ? ORDER BY id ASC LIMIT ?');
        $stmt->bindValue(1, $importId, PDO::PARAM_INT);
        $stmt->bindValue(2, $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function saveJob(array $payload, ?int $id = null): int
    {
        $title = smart_title((string) ($payload['title'] ?? ''));
        $companyId = (int) ($payload['company_id'] ?? 0);
        $cityId = (int) ($payload['city_id'] ?? 0);
        $categoryId = (int) ($payload['category_id'] ?? 0);
        $description = clean_html((string) ($payload['description'] ?? ''));
        $state = config('site.main_uf');
        if ($title === '' || $companyId < 1 || $cityId < 1 || $description === '') {
            throw new \InvalidArgumentException('Titulo, empresa, cidade e descricao sao obrigatorios.');
        }

        $slugBase = slugify($title . '-' . date('YmdHis'));
        $slug = $slugBase;
        $inc = 1;
        while ($this->slugExists($slug, $id)) {
            $slug = $slugBase . '-' . $inc++;
        }

        $now = date('c');
        $publishedAt = resolve_job_published_at((string) ($payload['published_at'] ?? ''));
        $validThroughRaw = trim((string) ($payload['valid_through'] ?? ''));
        $validThrough = $validThroughRaw !== ''
            ? resolve_job_valid_through($validThroughRaw, $publishedAt)
            : resolve_job_valid_through('', $publishedAt);
        $applyRaw = trim((string) ($payload['apply_url'] ?? ''));
        $applyUrl = $applyRaw === '' ? '' : (normalize_apply_url($applyRaw) ?? '');
        if ($applyRaw !== '' && $applyUrl === '') {
            throw new \InvalidArgumentException('Link ou e-mail de candidatura inválido.');
        }
        $isActive = isset($payload['is_active']) && (int) $payload['is_active'] === 1 ? 1 : 0;

        if ($id) {
            $current = $this->jobById($id);
            if (!$current) {
                throw new \InvalidArgumentException('Vaga nao encontrada.');
            }
            $stmt = $this->pdo->prepare(
                'UPDATE jobs SET title=?, company_id=?, category_id=?, city_id=?, state=?, description=?, requirements=?, activities=?, benefits=?, additional_info=?, salary=?, employment_type=?, apply_url=?, is_active=?, published_at=?, valid_through=?, updated_at=? WHERE id=?'
            );
            $employmentType = nullable_field((string) ($payload['employment_type'] ?? ''));
            $stmt->execute([
                $title,
                $companyId,
                $categoryId ?: null,
                $cityId,
                $state,
                $description,
                null,
                null,
                null,
                null,
                nullable_field((string) ($payload['salary'] ?? '')),
                $employmentType,
                $applyUrl,
                $isActive,
                $publishedAt,
                $validThrough,
                $now,
                $id,
            ]);
            return $id;
        }

        $employmentType = nullable_field((string) ($payload['employment_type'] ?? ''));
        $stmt = $this->pdo->prepare(
            'INSERT INTO jobs (title, slug, company_id, category_id, city_id, state, description, requirements, activities, benefits, additional_info, salary, employment_type, apply_url, is_active, published_at, valid_through, source, is_demo, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $title,
            $slug,
            $companyId,
            $categoryId ?: null,
            $cityId,
            $state,
            $description,
            null,
            null,
            null,
            null,
            nullable_field((string) ($payload['salary'] ?? '')),
            $employmentType,
            $applyUrl,
            $isActive,
            $publishedAt,
            $validThrough,
            'manual',
            0,
            $now,
            $now,
        ]);
        return (int) $this->pdo->lastInsertId();
    }

    public function jobById(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM jobs WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);
        $job = $stmt->fetch();
        return $job ?: null;
    }

    public function deleteJob(int $id): void
    {
        if ($id < 1) {
            throw new \InvalidArgumentException('Vaga inválida.');
        }
        $stmt = $this->pdo->prepare('DELETE FROM jobs WHERE id = ?');
        $stmt->execute([$id]);
        if ($stmt->rowCount() < 1) {
            throw new \InvalidArgumentException('Vaga não encontrada.');
        }
    }

    /** @param list<int|string> $ids */
    public function deleteJobsByIds(array $ids): int
    {
        $ids = array_values(array_unique(array_filter(array_map(static fn ($id) => (int) $id, $ids), static fn ($id) => $id > 0)));
        if ($ids === []) {
            throw new \InvalidArgumentException('Selecione ao menos uma vaga para excluir.');
        }

        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $this->pdo->prepare("DELETE FROM jobs WHERE id IN ({$placeholders})");
        $stmt->execute($ids);

        return $stmt->rowCount();
    }

    public function purgeExpiredJobs(): int
    {
        return ExpiredJobsPurge::run($this->pdo);
    }

    public function toggleJob(int $id): void
    {
        $stmt = $this->pdo->prepare('UPDATE jobs SET is_active = CASE WHEN is_active = 1 THEN 0 ELSE 1 END, updated_at = ? WHERE id = ?');
        $stmt->execute([date('c'), $id]);
    }

    public function importCsv(string $filePath): array
    {
        return $this->importSpreadsheet($filePath, basename($filePath));
    }

    public function importSpreadsheet(string $filePath, string $originalFilename): array
    {
        $ext = strtolower((string) pathinfo($originalFilename, PATHINFO_EXTENSION));
        if (!in_array($ext, ['csv', 'xlsx'], true)) {
            throw new \RuntimeException('Formato nao suportado. Envie .csv ou .xlsx.');
        }

        $rows = $ext === 'xlsx' ? $this->readXlsxRows($filePath) : $this->readCsvRows($filePath);
        if (count($rows) < 2) {
            throw new \RuntimeException('Planilha vazia ou sem dados.');
        }

        $header = array_map(static fn ($item) => trim((string) $item), (array) $rows[0]);
        $required = ['title', 'company', 'city', 'state', 'description', 'applyUrl'];
        foreach ($required as $field) {
            if (!in_array($field, $header, true)) {
                throw new \RuntimeException("Campo obrigatorio ausente na planilha: {$field}");
            }
        }

        $createdAt = date('c');
        $stmtImport = $this->pdo->prepare('INSERT INTO imports (filename, created_at) VALUES (?, ?)');
        $stmtImport->execute([basename($originalFilename), $createdAt]);
        $importId = (int) $this->pdo->lastInsertId();

        $total = 0;
        $ok = 0;
        $ignored = 0;
        $errors = 0;
        $errorReasons = [];
        $cityWarnings = [];

        for ($line = 1, $max = count($rows); $line < $max; $line++) {
            $row = (array) $rows[$line];
            if ($this->isEmptyRow($row)) {
                continue;
            }
            $total++;
            $record = [];
            foreach ($header as $index => $column) {
                $record[$column] = isset($row[$index]) ? trim((string) $row[$index]) : '';
            }

            try {
                $state = strtoupper((string) ($record['state'] ?? ''));
                if ($state !== config('site.main_uf')) {
                    $ignored++;
                    $this->saveImportError($importId, $line + 1, 'UF diferente da configurada', json_encode($record, JSON_UNESCAPED_UNICODE));
                    continue;
                }

                $title = smart_title((string) ($record['title'] ?? ''));
                $companyName = smart_title((string) ($record['company'] ?? ''));
                $cityName = smart_title((string) ($record['city'] ?? ''));
                $description = merge_import_description($record);
                if ($title === '' || $companyName === '' || $cityName === '' || $description === '') {
                    $errors++;
                    $this->saveImportError($importId, $line + 1, 'Campos obrigatorios invalidos', json_encode($record, JSON_UNESCAPED_UNICODE));
                    continue;
                }

                $applyUrl = normalize_apply_url((string) ($record['applyUrl'] ?? ''));
                if ($applyUrl === null) {
                    $errors++;
                    $this->saveImportError($importId, $line + 1, 'applyUrl invalido: informe URL http(s) ou e-mail valido', json_encode($record, JSON_UNESCAPED_UNICODE));
                    continue;
                }

                $canonicalCity = match_allowed_city_name($cityName);
                if ($canonicalCity === null) {
                    $ignored++;
                    $reason = "Cidade '{$cityName}' nao pertence ao RJ";
                    $cityWarnings[] = "Linha " . ($line + 1) . ": {$reason}";
                    $this->saveImportError($importId, $line + 1, $reason, json_encode($record, JSON_UNESCAPED_UNICODE));
                    continue;
                }
                $cityName = $canonicalCity;

                $companyId = $this->firstOrCreateCompany($companyName);
                $cityId = $this->firstOrCreateCity($cityName, $state);
                $categoryId = null;
                if (!empty($record['category'])) {
                    $categoryId = $this->firstOrCreateCategory(normalize_category_name((string) $record['category']));
                }

                $publishedAt = resolve_job_published_at((string) ($record['publishedAt'] ?? ''));
                $validThrough = resolve_job_valid_through((string) ($record['validThrough'] ?? ''), $publishedAt);
                $slugBase = slugify($title . '-' . $cityName . '-' . config('site.main_uf'));
                $slug = $slugBase;
                $inc = 1;
                while ($this->slugExists($slug, null)) {
                    $slug = $slugBase . '-' . $inc++;
                }

                $stmt = $this->pdo->prepare(
                    'INSERT INTO jobs (title, slug, company_id, category_id, city_id, state, description, requirements, activities, benefits, additional_info, salary, employment_type, apply_url, is_active, published_at, valid_through, source, is_demo, created_at, updated_at)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
                );
                $employmentType = nullable_field((string) ($record['employmentType'] ?? ''));
                $salary = nullable_field((string) ($record['salary'] ?? ''));
                $stmt->execute([
                    $title,
                    $slug,
                    $companyId,
                    $categoryId,
                    $cityId,
                    $state,
                    $description,
                    null,
                    null,
                    null,
                    null,
                    $salary,
                    $employmentType,
                    $applyUrl,
                    1,
                    $publishedAt,
                    $validThrough,
                    $ext,
                    0,
                    date('c'),
                    date('c'),
                ]);
                $ok++;
            } catch (\Throwable $e) {
                $errors++;
                $errorReasons[] = $e->getMessage();
                $this->saveImportError($importId, $line + 1, $e->getMessage(), json_encode($record, JSON_UNESCAPED_UNICODE));
            }
        }

        $summary = [
            'import_id' => $importId,
            'total_rows' => $total,
            'imported_rows' => $ok,
            'ignored_rows' => $ignored,
            'error_rows' => $errors,
            'city_warnings' => array_slice($cityWarnings, 0, 8),
            'city_warning_rows' => count($cityWarnings),
            'errors_preview' => array_slice($errorReasons, 0, 8),
        ];
        $update = $this->pdo->prepare('UPDATE imports SET total_rows=?, imported_rows=?, ignored_rows=?, error_rows=?, summary_json=? WHERE id=?');
        $update->execute([$total, $ok, $ignored, $errors, json_encode($summary, JSON_UNESCAPED_UNICODE), $importId]);

        return $summary;
    }

    public function buildSitemapUrls(): array
    {
        $sitemap = new SitemapService($this->pdo, $this);
        $urls = [];
        foreach ($sitemap->indexLocations() as $loc) {
            $path = (string) parse_url($loc, PHP_URL_PATH);
            $chunk = $sitemap->chunkByRequestPath($path);
            if ($chunk) {
                foreach ($chunk['urls'] as $entry) {
                    $urls[] = $entry;
                }
            }
        }

        return $urls;
    }

    private function slugExists(string $slug, ?int $ignoreId): bool
    {
        if ($ignoreId) {
            $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM jobs WHERE slug = ? AND id != ?');
            $stmt->execute([$slug, $ignoreId]);
            return (int) $stmt->fetchColumn() > 0;
        }
        $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM jobs WHERE slug = ?');
        $stmt->execute([$slug]);
        return (int) $stmt->fetchColumn() > 0;
    }

    private function firstOrCreateCompany(string $name): int
    {
        $stmt = $this->pdo->prepare('SELECT id FROM companies WHERE name = ? LIMIT 1');
        $stmt->execute([$name]);
        $id = (int) $stmt->fetchColumn();
        if ($id > 0) {
            return $id;
        }
        $insert = $this->pdo->prepare('INSERT INTO companies (name, slug, created_at) VALUES (?, ?, ?)');
        $insert->execute([$name, slugify($name), date('c')]);
        return (int) $this->pdo->lastInsertId();
    }

    private function firstOrCreateCity(string $name, string $state): int
    {
        $canonical = match_allowed_city_name($name);
        if ($canonical === null) {
            throw new \InvalidArgumentException("Cidade '{$name}' nao pertence ao RJ.");
        }
        $name = $canonical;
        $stmt = $this->pdo->prepare('SELECT id FROM cities WHERE name = ? AND state = ? LIMIT 1');
        $stmt->execute([$name, $state]);
        $id = (int) $stmt->fetchColumn();
        if ($id > 0) {
            return $id;
        }
        $insert = $this->pdo->prepare('INSERT INTO cities (name, slug, state, created_at) VALUES (?, ?, ?, ?)');
        $insert->execute([$name, city_slug($name), $state, date('c')]);
        return (int) $this->pdo->lastInsertId();
    }

    /** @return array{0:string,1:array<string,string>} */
    private function allowedCityFilter(string $column): array
    {
        $names = allowed_rj_cities();
        if ($names === []) {
            return ['1=0', []];
        }
        $placeholders = [];
        $params = [];
        foreach ($names as $index => $name) {
            $key = ':allowed_city_' . $index;
            $placeholders[] = $key;
            $params[$key] = $name;
        }
        return [$column . ' IN (' . implode(',', $placeholders) . ')', $params];
    }

    private function firstOrCreateCategory(string $name): int
    {
        $name = normalize_category_name($name);
        $stmt = $this->pdo->prepare('SELECT id FROM categories WHERE name = ? LIMIT 1');
        $stmt->execute([$name]);
        $id = (int) $stmt->fetchColumn();
        if ($id > 0) {
            return $id;
        }
        $insert = $this->pdo->prepare('INSERT INTO categories (name, slug, created_at) VALUES (?, ?, ?)');
        $insert->execute([$name, slugify($name), date('c')]);
        return (int) $this->pdo->lastInsertId();
    }

    private function saveImportError(int $importId, int $row, string $reason, ?string $raw): void
    {
        $stmt = $this->pdo->prepare('INSERT INTO import_errors (import_id, row_number, reason, raw_data) VALUES (?, ?, ?, ?)');
        $stmt->execute([$importId, $row, mb_substr($reason, 0, 250), $raw]);
    }

    private function readCsvRows(string $filePath): array
    {
        $handle = fopen($filePath, 'rb');
        if (!$handle) {
            throw new \RuntimeException('Falha ao abrir o arquivo CSV.');
        }
        $rows = [];
        while (($row = fgetcsv($handle)) !== false) {
            $rows[] = array_map(static fn ($item) => trim((string) $item), $row);
        }
        fclose($handle);
        return $rows;
    }

    private function readXlsxRows(string $filePath): array
    {
        if (!class_exists(\ZipArchive::class)) {
            throw new \RuntimeException('Extensao ZipArchive nao disponivel para leitura de XLSX.');
        }

        $zip = new \ZipArchive();
        if ($zip->open($filePath) !== true) {
            throw new \RuntimeException('Falha ao abrir o arquivo XLSX.');
        }

        $sharedStringsXml = $zip->getFromName('xl/sharedStrings.xml');
        $sheetXml = $zip->getFromName('xl/worksheets/sheet1.xml');
        if ($sheetXml === false) {
            $zip->close();
            throw new \RuntimeException('Planilha XLSX invalida: sheet1.xml ausente.');
        }

        $shared = [];
        if ($sharedStringsXml !== false) {
            $sharedDoc = simplexml_load_string($sharedStringsXml);
            if ($sharedDoc !== false && isset($sharedDoc->si)) {
                foreach ($sharedDoc->si as $si) {
                    $text = '';
                    if (isset($si->t)) {
                        $text = (string) $si->t;
                    } elseif (isset($si->r)) {
                        foreach ($si->r as $run) {
                            $text .= (string) $run->t;
                        }
                    }
                    $shared[] = $text;
                }
            }
        }

        $sheet = simplexml_load_string($sheetXml);
        $zip->close();
        if ($sheet === false || !isset($sheet->sheetData->row)) {
            throw new \RuntimeException('Planilha XLSX invalida: sem linhas.');
        }

        $rows = [];
        foreach ($sheet->sheetData->row as $row) {
            $parsed = [];
            $current = 0;
            foreach ($row->c as $cell) {
                $ref = (string) $cell['r'];
                $letters = preg_replace('/\d+/', '', $ref) ?: 'A';
                $target = $this->columnLettersToIndex($letters);
                while ($current < $target) {
                    $parsed[] = '';
                    $current++;
                }

                $type = (string) $cell['t'];
                $value = isset($cell->v) ? (string) $cell->v : '';
                if ($type === 's') {
                    $idx = (int) $value;
                    $value = $shared[$idx] ?? '';
                }
                $parsed[] = trim($value);
                $current++;
            }
            $rows[] = $parsed;
        }
        return $rows;
    }

    private function columnLettersToIndex(string $letters): int
    {
        $letters = strtoupper($letters);
        $index = 0;
        for ($i = 0, $l = strlen($letters); $i < $l; $i++) {
            $index = $index * 26 + (ord($letters[$i]) - 64);
        }
        return max(0, $index - 1);
    }

    private function isEmptyRow(array $row): bool
    {
        foreach ($row as $value) {
            if (trim((string) $value) !== '') {
                return false;
            }
        }
        return true;
    }

    private function jobNotExpiredCondition(string $alias = 'j'): string
    {
        return sprintf(
            '(%s.valid_through IS NULL OR TRIM(%s.valid_through) = \'\' OR %s.valid_through >= :now_valid)',
            $alias,
            $alias,
            $alias
        );
    }
}
