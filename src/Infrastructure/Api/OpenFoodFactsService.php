<?php

declare(strict_types=1);

namespace Infrastructure\Api;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Psr\Log\LoggerInterface;

final class OpenFoodFactsService
{
    // On utilise l'API v2 OpenFoodFactsService 
    // SAUF pour searchByName qui utilise /cgi/search.pl (v1)
    // car la v2 ne supporte pas la recherche par mots-clés
    private const API_BASE_URL = 'https://world.openfoodfacts.org';
    private const API_VERSION  = 'v2';

    // Timeout augmenté pour les recherches longues
    private const TIMEOUT_SEARCH = 30;
    private const TIMEOUT_PRODUCT = 10;

    private HttpClientInterface $httpClient;
    private array $cache = [];

    public function __construct(
        private readonly LoggerInterface $logger,
    ) {
        $this->httpClient = \Symfony\Component\HttpClient\HttpClient::create([
            'base_uri' => self::API_BASE_URL,
            'headers'  => [
                'User-Agent' => 'MMM/1.0 (contact@mmm-app.com)',
            ],
            'timeout' => self::TIMEOUT_PRODUCT,
        ]);
    }

    // RECHERCHE PAR NOM / MARQUE
    // Utilise /cgi/search.pl (v1) car v2 ne supporte pas les
    // mots-clés. Rate limit : 100 req/min
    public function searchByName(string $query, int $limit = 10): array
    {
        $query = trim($query);

        if (strlen($query) < 2) {
            return [];
        }

        $cacheKey = 'search_name_' . md5($query) . "_{$limit}";

        if (isset($this->cache[$cacheKey])) {
            return $this->cache[$cacheKey];
        }

        try {
            $response = $this->httpClient->request('GET', '/cgi/search.pl', [
                'timeout' => self::TIMEOUT_SEARCH,
                'query'   => [
                    'search_terms' => $query,
                    'search_simple' => 1,
                    'action'       => 'process',
                    'json'         => 1,
                    'page_size'    => min($limit, 20),
                    'fields'       => 'code,product_name,product_name_fr,brands,nutriscore_grade,quantity',
                    'lc'           => 'fr', 
                    'cc'           => 'fr', 
                ],
            ]);

            $data = $response->toArray();

            // Normaliser les produits : privilégier le nom français si disponible
            $products = array_map(function (array $p): array {
                $p['product_name'] = $p['product_name_fr']
                    ?? $p['product_name']
                    ?? null;
                return $p;
            }, $data['products'] ?? []);

            // Filtrer les produits sans nom ET sans code-barres
            $products = array_values(array_filter(
                $products,
                fn ($p) => !empty($p['product_name']) && !empty($p['code'])
            ));

            $this->cache[$cacheKey] = $products;

            return $products;

        } catch (\Exception $e) {
            $this->logger->error('OpenFoodFacts API error (searchByName)', [
                'query' => $query,
                'error' => $e->getMessage(),
            ]);
            return [];
        }
    }

    // RÉCUPÉRATION PAR CODE-BARRES
    // Utilise /api/v2/product/{barcode}
    // Rate limit : 100 req/min
    public function getProduct(string $barcode): ?array
    {
        $cacheKey = "product_{$barcode}";

        if (isset($this->cache[$cacheKey])) {
            return $this->cache[$cacheKey];
        }

        try {
            $response = $this->httpClient->request(
                'GET',
                sprintf('/api/%s/product/%s', self::API_VERSION, $barcode),
                [
                    'query' => [
                        'fields' => 'code,product_name,nutriscore_grade,nova_group,nutriments,categories_tags,brands',
                    ],
                ]
            );

            $data = $response->toArray();

            if (($data['status'] ?? 0) === 1) {
                $product = $data['product'] ?? null;
                $this->cache[$cacheKey] = $product;
                return $product;
            }

            return null;

        } catch (\Exception $e) {
            $this->logger->error('OpenFoodFacts API error (getProduct)', [
                'barcode' => $barcode,
                'error'   => $e->getMessage(),
            ]);
            return null;
        }
    }

    public function getProductDetails(string $barcode): ?array
    {
        $cacheKey = "product_details_{$barcode}";

        if (isset($this->cache[$cacheKey])) {
            return $this->cache[$cacheKey];
        }

        try {
            $response = $this->httpClient->request(
                'GET',
                sprintf('/api/%s/product/%s', self::API_VERSION, $barcode),
                [
                    'query' => [
                        'fields' => 'code,product_name,brands,nutriscore_grade,nova_group,nutriments,categories_tags,quantity,origins_tags,url',
                    ],
                ]
            );

            $data = $response->toArray();

            if (($data['status'] ?? 0) === 1) {
                $product = $data['product'] ?? null;
                if ($product) {
                    $product['openfoodfacts_url'] = "https://fr.openfoodfacts.org/produit/{$barcode}";
                    $this->cache[$cacheKey] = $product;
                    return $product;
                }
            }

            return null;

        } catch (\Exception $e) {
            $this->logger->error('OpenFoodFacts API error (getProductDetails)', [
                'barcode' => $barcode,
                'error'   => $e->getMessage(),
            ]);
            return null;
        }
    }

    public function getProductByBarcode(string $barcode): ?array
    {
        return $this->getProductDetails($barcode);
    }

    // RÉCUPÉRATION DE PLUSIEURS PRODUITS
    // Utilise /api/v2/search avec filtre par code
    public function getMultipleProducts(array $barcodes): array
    {
        if (empty($barcodes)) {
            return [];
        }

        $cacheKey = 'multi_' . md5(implode(',', $barcodes));

        if (isset($this->cache[$cacheKey])) {
            return $this->cache[$cacheKey];
        }

        try {
            $response = $this->httpClient->request(
                'GET',
                sprintf('/api/%s/search', self::API_VERSION),
                [
                    'query' => [
                        'code'   => implode(',', $barcodes),
                        'fields' => 'code,product_name,brands,nutriscore_grade,nova_group,nutriments',
                    ],
                ]
            );

            $data     = $response->toArray();
            $products = $data['products'] ?? [];

            $this->cache[$cacheKey] = $products;

            return $products;

        } catch (\Exception $e) {
            $this->logger->error('OpenFoodFacts API error (getMultipleProducts)', [
                'barcodes' => $barcodes,
                'error'    => $e->getMessage(),
            ]);
            return [];
        }
    }

    // RECHERCHE PAR FILTRES (v2)
    // Utilisé pour nutriscore, nova, catégories...
    // Rate limit : 10 req/min → sleep(6) pour éviter un ban
    public function search(array $filters = [], int $page = 1, int $pageSize = 20): array
    {
        $cacheKey = 'search_' . md5(json_encode($filters) . "_{$page}_{$pageSize}");

        if (isset($this->cache[$cacheKey])) {
            return $this->cache[$cacheKey];
        }

        try {
            // Rate limit v2 : 10 req/min → on attend 6s entre chaque appel
            sleep(6);

            $query = array_merge($filters, [
                'page'      => $page,
                'page_size' => min($pageSize, 100),
                'fields'    => 'code,product_name,nutriscore_grade,nova_group,nutriments',
            ]);

            $response = $this->httpClient->request(
                'GET',
                sprintf('/api/%s/search', self::API_VERSION),
                ['query' => $query, 'timeout' => self::TIMEOUT_SEARCH]
            );

            $data = $response->toArray();
            $this->cache[$cacheKey] = $data;

            return $data;

        } catch (\Exception $e) {
            $this->logger->error('OpenFoodFacts API error (search)', [
                'filters' => $filters,
                'error'   => $e->getMessage(),
            ]);
            return ['products' => [], 'count' => 0, 'page' => $page];
        }
    }

    // MÉTHODES UTILITAIRES 
    public function getNutriscoreStats(int $limit = 30): array
    {
        $cacheKey = "nutriscore_stats_{$limit}";

        if (isset($this->cache[$cacheKey])) {
            return $this->cache[$cacheKey];
        }

        $stats = ['a' => 8, 'b' => 15, 'c' => 25, 'd' => 20, 'e' => 12];

        try {
            $result = $this->search([], 1, min($limit, 30));

            if (!empty($result['products'])) {
                $counts = ['a' => 0, 'b' => 0, 'c' => 0, 'd' => 0, 'e' => 0];

                foreach ($result['products'] as $product) {
                    $grade = strtolower($product['nutriscore_grade'] ?? '');
                    if (isset($counts[$grade])) {
                        $counts[$grade]++;
                    }
                }

                if (array_sum($counts) > 0) {
                    $stats = $counts;
                }
            }

            $this->cache[$cacheKey] = $stats;
            return $stats;

        } catch (\Exception $e) {
            $this->logger->warning('Failed to get Nutriscore stats, using defaults', [
                'error' => $e->getMessage(),
            ]);
            return $stats;
        }
    }

    public function getNovaGroupStats(int $limit = 30): array
    {
        $cacheKey = "nova_stats_{$limit}";

        if (isset($this->cache[$cacheKey])) {
            return $this->cache[$cacheKey];
        }

        $stats = ['1' => 10, '2' => 15, '3' => 30, '4' => 25];

        try {
            $result = $this->search([], 1, min($limit, 30));

            if (!empty($result['products'])) {
                $counts = ['1' => 0, '2' => 0, '3' => 0, '4' => 0];

                foreach ($result['products'] as $product) {
                    $novaGroup = (string) ($product['nova_group'] ?? '0');
                    if (isset($counts[$novaGroup])) {
                        $counts[$novaGroup]++;
                    }
                }

                if (array_sum($counts) > 0) {
                    $stats = $counts;
                }
            }

            $this->cache[$cacheKey] = $stats;
            return $stats;

        } catch (\Exception $e) {
            $this->logger->warning('Failed to get NOVA stats, using defaults', [
                'error' => $e->getMessage(),
            ]);
            return $stats;
        }
    }

    public function searchByCategory(string $category, int $limit = 20): array
    {
        $result = $this->search(['categories_tags' => $category], 1, $limit);
        return $result['products'] ?? [];
    }

    public function getAverageNutriments(?string $category = null, int $limit = 30): array
    {
        $cacheKey = 'avg_nutriments_' . ($category ?? 'all') . "_{$limit}";

        if (isset($this->cache[$cacheKey])) {
            return $this->cache[$cacheKey];
        }

        $defaults = [
            'energy'        => 350,
            'fat'           => 12,
            'saturated_fat' => 5,
            'carbohydrates' => 45,
            'sugars'        => 20,
            'proteins'      => 8,
            'salt'          => 1.2,
        ];

        try {
            $filters = $category ? ['categories_tags' => $category] : [];
            $result  = $this->search($filters, 1, min($limit, 30));

            $totals = array_fill_keys(array_keys($defaults), 0);
            $count  = 0;

            foreach ($result['products'] ?? [] as $product) {
                $nutriments = $product['nutriments'] ?? [];

                if (isset($nutriments['energy-kcal_100g'])) {
                    $totals['energy']        += $nutriments['energy-kcal_100g'] ?? 0;
                    $totals['fat']           += $nutriments['fat_100g'] ?? 0;
                    $totals['saturated_fat'] += $nutriments['saturated-fat_100g'] ?? 0;
                    $totals['carbohydrates'] += $nutriments['carbohydrates_100g'] ?? 0;
                    $totals['sugars']        += $nutriments['sugars_100g'] ?? 0;
                    $totals['proteins']      += $nutriments['proteins_100g'] ?? 0;
                    $totals['salt']          += $nutriments['salt_100g'] ?? 0;
                    $count++;
                }
            }

            $averages = $count > 0
                ? array_map(fn ($v) => round($v / $count, 2), $totals)
                : $defaults;

            $this->cache[$cacheKey] = $averages;
            return $averages;

        } catch (\Exception $e) {
            $this->logger->warning('Failed to get average nutriments, using defaults', [
                'error' => $e->getMessage(),
            ]);
            return $defaults;
        }
    }

    public function searchByNutriscore(array $grades, int $limit = 30): array
    {
        $allProducts = [];

        foreach ($grades as $grade) {
            try {
                $result = $this->search(
                    ['nutrition_grades_tags' => strtolower($grade)],
                    1,
                    min(10, (int) ($limit / count($grades)))
                );

                if (isset($result['products'])) {
                    $allProducts = array_merge($allProducts, $result['products']);
                }
            } catch (\Exception $e) {
                $this->logger->warning('Failed to search by nutriscore', [
                    'grade' => $grade,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $allProducts;
    }
}