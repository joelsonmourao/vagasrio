<?php

declare(strict_types=1);

namespace App\Services;

use PDO;

final class SitemapService
{
    public const MAX_URLS_PER_FILE = 45000;

    private PDO $pdo;
    private PortalService $portal;

    public function __construct(PDO $pdo, PortalService $portal)
    {
        $this->pdo = $pdo;
        $this->portal = $portal;
    }

    /** @return list<string> */
    public function indexLocations(): array
    {
        $locations = [];
        foreach ($this->chunkedTypes() as $type => $chunks) {
            foreach ($chunks as $chunk) {
                $locations[] = base_url('/sitemap-' . $chunk['filename']);
            }
        }

        return $locations;
    }

    /**
     * @return array{filename:string,urls:list<array{loc:string,lastmod?:string,priority?:string}>}
     */
    public function chunkByRequestPath(string $requestPath): ?array
    {
        $basename = basename($requestPath, '.xml');
        if (!str_starts_with($basename, 'sitemap-')) {
            return null;
        }
        $filename = substr($basename, 8) . '.xml';

        return $this->chunkByFilename($filename);
    }

    /** @return array{filename:string,urls:list<array{loc:string,lastmod?:string,priority?:string}>}|null */
    private function chunkByFilename(string $filename): ?array
    {
        foreach ($this->chunkedTypes() as $chunks) {
            foreach ($chunks as $chunk) {
                if ($chunk['filename'] === $filename) {
                    return $chunk;
                }
            }
        }

        return null;
    }

    /** @return array<string, list<array{filename:string,urls:list<array{loc:string,lastmod?:string,priority?:string}>}>> */
    private function chunkedTypes(): array
    {
        return [
            'pages' => $this->chunkUrls($this->pagesUrls(), 'pages'),
            'jobs' => $this->chunkUrls($this->jobsUrls(), 'jobs'),
            'blog' => $this->chunkUrls($this->blogUrls(), 'blog'),
            'cities' => $this->chunkUrls($this->citiesUrls(), 'cities'),
            'categories' => $this->chunkUrls($this->categoriesUrls(), 'categories'),
            'companies' => $this->chunkUrls($this->companiesUrls(), 'companies'),
        ];
    }

    /** @param list<array{loc:string,lastmod?:string,priority?:string}> $urls */
    /** @return list<array{filename:string,urls:list<array{loc:string,lastmod?:string,priority?:string}>}> */
    private function chunkUrls(array $urls, string $prefix): array
    {
        if ($urls === []) {
            return [['filename' => $prefix . '.xml', 'urls' => []]];
        }

        $chunks = array_chunk($urls, self::MAX_URLS_PER_FILE);
        $result = [];
        foreach ($chunks as $index => $chunk) {
            $suffix = count($chunks) > 1 ? '-' . ($index + 1) : '';
            $result[] = [
                'filename' => $prefix . $suffix . '.xml',
                'urls' => $chunk,
            ];
        }

        return $result;
    }

    /** @return list<array{loc:string,priority:string}> */
    private function pagesUrls(): array
    {
        return [
            ['loc' => base_url('/'), 'priority' => '1.0'],
            ['loc' => base_url('/vagas'), 'priority' => '0.9'],
            ['loc' => base_url('/cidades'), 'priority' => '0.8'],
            ['loc' => base_url('/empresas'), 'priority' => '0.7'],
            ['loc' => base_url('/categorias'), 'priority' => '0.7'],
            ['loc' => base_url('/blog'), 'priority' => '0.7'],
            ['loc' => base_url('/sobre'), 'priority' => '0.6'],
            ['loc' => base_url('/contato'), 'priority' => '0.6'],
            ['loc' => base_url('/politica-de-privacidade'), 'priority' => '0.5'],
            ['loc' => base_url('/politica-de-cookies'), 'priority' => '0.5'],
            ['loc' => base_url('/termos-de-uso'), 'priority' => '0.5'],
            ['loc' => base_url('/aviso-legal'), 'priority' => '0.5'],
            ['loc' => base_url('/seguranca-para-candidatos'), 'priority' => '0.5'],
            ['loc' => base_url('/mapa-do-site'), 'priority' => '0.4'],
        ];
    }

    /** @return list<array{loc:string,lastmod?:string,priority:string}> */
    private function jobsUrls(): array
    {
        $uf = (string) config('site.main_uf');
        $now = date('c');
        $stmt = $this->pdo->prepare(
            "SELECT slug, updated_at, valid_through
             FROM jobs
             WHERE is_active = 1 AND state = ?
             AND (valid_through IS NULL OR valid_through = '' OR valid_through >= ?)
             ORDER BY published_at DESC"
        );
        $stmt->execute([$uf, $now]);
        $urls = [];
        while ($row = $stmt->fetch()) {
            $lastmod = $this->isoDate((string) ($row['updated_at'] ?? ''));
            $entry = [
                'loc' => base_url('/vagas/' . $row['slug']),
                'priority' => '0.8',
            ];
            if ($lastmod !== null) {
                $entry['lastmod'] = $lastmod;
            }
            $urls[] = $entry;
        }

        return $urls;
    }

    /** @return list<array{loc:string,lastmod?:string,priority:string}> */
    private function blogUrls(): array
    {
        $urls = [
            ['loc' => base_url('/blog'), 'priority' => '0.7'],
        ];

        foreach ($this->portal->blogCategories() as $category) {
            $urls[] = [
                'loc' => base_url('/blog/categoria/' . $category['slug']),
                'priority' => '0.6',
            ];
        }

        $stmt = $this->pdo->query(
            "SELECT slug, updated_at, published_at FROM blog_posts WHERE is_active = 1 ORDER BY published_at DESC"
        );
        while ($row = $stmt->fetch()) {
            $lastmod = $this->isoDate((string) ($row['updated_at'] ?? $row['published_at'] ?? ''));
            $entry = [
                'loc' => base_url('/blog/' . $row['slug']),
                'priority' => '0.6',
            ];
            if ($lastmod !== null) {
                $entry['lastmod'] = $lastmod;
            }
            $urls[] = $entry;
        }

        return $urls;
    }

    /** @return list<array{loc:string,priority:string}> */
    private function citiesUrls(): array
    {
        $urls = [['loc' => base_url('/cidades'), 'priority' => '0.8']];
        foreach ($this->portal->cities() as $city) {
            $urls[] = [
                'loc' => base_url('/cidades/' . $city['slug']),
                'priority' => '0.7',
            ];
        }

        return $urls;
    }

    /** @return list<array{loc:string,priority:string}> */
    private function categoriesUrls(): array
    {
        $urls = [['loc' => base_url('/categorias'), 'priority' => '0.7']];
        foreach ($this->portal->categories() as $category) {
            $urls[] = [
                'loc' => base_url('/categorias/' . $category['slug']),
                'priority' => '0.6',
            ];
        }

        return $urls;
    }

    /** @return list<array{loc:string,priority:string}> */
    private function companiesUrls(): array
    {
        $urls = [['loc' => base_url('/empresas'), 'priority' => '0.7']];
        foreach ($this->portal->companies() as $company) {
            $urls[] = [
                'loc' => base_url('/empresas/' . $company['slug']),
                'priority' => '0.6',
            ];
        }

        return $urls;
    }

    private function isoDate(string $stored): ?string
    {
        if (trim($stored) === '') {
            return null;
        }
        try {
            return (new \DateTimeImmutable($stored))->format('Y-m-d');
        } catch (\Exception) {
            return null;
        }
    }

    /** @param list<array{loc:string,lastmod?:string,priority?:string}> $urls */
    public static function renderUrlset(array $urls): string
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>';
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
        foreach ($urls as $url) {
            $xml .= '<url><loc>' . htmlspecialchars($url['loc'], ENT_XML1 | ENT_QUOTES, 'UTF-8') . '</loc>';
            if (!empty($url['lastmod'])) {
                $xml .= '<lastmod>' . htmlspecialchars($url['lastmod'], ENT_XML1 | ENT_QUOTES, 'UTF-8') . '</lastmod>';
            }
            if (!empty($url['priority'])) {
                $xml .= '<priority>' . htmlspecialchars($url['priority'], ENT_XML1 | ENT_QUOTES, 'UTF-8') . '</priority>';
            }
            $xml .= '</url>';
        }
        $xml .= '</urlset>';

        return $xml;
    }

    /** @param list<string> $locations */
    public static function renderIndex(array $locations): string
    {
        $now = date('c');
        $xml = '<?xml version="1.0" encoding="UTF-8"?>';
        $xml .= '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
        foreach ($locations as $loc) {
            $xml .= '<sitemap><loc>' . htmlspecialchars($loc, ENT_XML1 | ENT_QUOTES, 'UTF-8') . '</loc>';
            $xml .= '<lastmod>' . htmlspecialchars($now, ENT_XML1 | ENT_QUOTES, 'UTF-8') . '</lastmod>';
            $xml .= '</sitemap>';
        }
        $xml .= '</sitemapindex>';

        return $xml;
    }
}
