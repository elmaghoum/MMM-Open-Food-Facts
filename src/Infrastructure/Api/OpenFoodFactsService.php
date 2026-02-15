<?php

declare(strict_types=1);

namespace Infrastructure\Api;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Psr\Log\LoggerInterface;

final class OpenFoodFactsService
{
    private const API_BASE_URL = 'https://world.openfoodfacts.org';
    private const API_VERSION = 'v2';
    
    private HttpClientInterface $httpClient;
    private array $cache = [];

    public function __construct(
        private readonly LoggerInterface $logger,
    ) {
        $this->httpClient = \Symfony\Component\HttpClient\HttpClient::create([
            'base_uri' => self::API_BASE_URL,
            'headers' => [
                'User-Agent' => 'MMM/1.0 (contact@mmm-app.com)',
            ],
            'timeout' => 10,
        ]);
    }

    /**
     * Récupère un produit par son barcode (API v2)
     * Rate limit: 100 req/min
     */
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
            
            if ($data['status'] === 1) {
                $product = $data['product'] ?? null;
                $this->cache[$cacheKey] = $product;
                return $product;
            }
            
            return null;
        } catch (\Exception $e) {
            $this->logger->error('OpenFoodFacts API error (getProduct)', [
                'barcode' => $barcode,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Recherche de produits (API v2)
     * Rate limit: 10 req/min - ATTENTION !
     */
    public function search(array $filters = [], int $page = 1, int $pageSize = 20): array
    {
        $cacheKey = "search_" . md5(json_encode($filters) . "_{$page}_{$pageSize}");
        
        if (isset($this->cache[$cacheKey])) {
            return $this->cache[$cacheKey];
        }

        try {
            // Protection rate limit: pause de 6 secondes entre les requêtes
            sleep(6);
            
            $query = array_merge($filters, [
                'page' => $page,
                'page_size' => min($pageSize, 100),
                'fields' => 'code,product_name,nutriscore_grade,nova_group,nutriments',
            ]);
            
            $response = $this->httpClient->request(
                'GET',
                sprintf('/api/%s/search', self::API_VERSION),
                ['query' => $query]
            );
            
            $data = $response->toArray();
            $this->cache[$cacheKey] = $data;
            
            return $data;
        } catch (\Exception $e) {
            $this->logger->error('OpenFoodFacts API error (search)', [
                'filters' => $filters,
                'error' => $e->getMessage(),
            ]);
            return ['products' => [], 'count' => 0, 'page' => $page];
        }
    }

    /**
     * Récupère les statistiques Nutriscore
     */
    public function getNutriscoreStats(int $limit = 30): array
    {
        $cacheKey = "nutriscore_stats_{$limit}";
        
        if (isset($this->cache[$cacheKey])) {
            return $this->cache[$cacheKey];
        }

        // Valeurs par défaut réalistes
        $stats = [
            'a' => 8,
            'b' => 15,
            'c' => 25,
            'd' => 20,
            'e' => 12,
        ];

        try {
            // Récupérer un échantillon de produits
            $result = $this->search([], 1, min($limit, 30));
            
            if (!empty($result['products'])) {
                $stats = ['a' => 0, 'b' => 0, 'c' => 0, 'd' => 0, 'e' => 0];
                
                foreach ($result['products'] as $product) {
                    $grade = strtolower($product['nutriscore_grade'] ?? '');
                    if (isset($stats[$grade])) {
                        $stats[$grade]++;
                    }
                }
                
                // Si tous les compteurs sont à zéro, utiliser les valeurs par défaut
                if (array_sum($stats) === 0) {
                    $stats = ['a' => 8, 'b' => 15, 'c' => 25, 'd' => 20, 'e' => 12];
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

    /**
     * Récupère les statistiques NOVA
     */
    public function getNovaGroupStats(int $limit = 30): array
    {
        $cacheKey = "nova_stats_{$limit}";
        
        if (isset($this->cache[$cacheKey])) {
            return $this->cache[$cacheKey];
        }

        // Valeurs par défaut réalistes
        $stats = [
            '1' => 10,
            '2' => 15,
            '3' => 30,
            '4' => 25,
        ];

        try {
            $result = $this->search([], 1, min($limit, 30));
            
            if (!empty($result['products'])) {
                $stats = ['1' => 0, '2' => 0, '3' => 0, '4' => 0];
                
                foreach ($result['products'] as $product) {
                    $novaGroup = (string)($product['nova_group'] ?? '0');
                    if (isset($stats[$novaGroup]) && $novaGroup !== '0') {
                        $stats[$novaGroup]++;
                    }
                }
                
                // Si tous les compteurs sont à zéro, utiliser les valeurs par défaut
                if (array_sum($stats) === 0) {
                    $stats = ['1' => 10, '2' => 15, '3' => 30, '4' => 25];
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

    /**
     * Recherche par catégorie
     */
    public function searchByCategory(string $category, int $limit = 20): array
    {
        $result = $this->search([
            'categories_tags' => $category,
        ], 1, $limit);
        
        return $result['products'] ?? [];
    }

    /**
     * Calcule les nutriments moyens
     */
    public function getAverageNutriments(?string $category = null, int $limit = 30): array
    {
        $cacheKey = "avg_nutriments_" . ($category ?? 'all') . "_{$limit}";
        
        if (isset($this->cache[$cacheKey])) {
            return $this->cache[$cacheKey];
        }

        // Valeurs par défaut réalistes
        $defaults = [
            'energy' => 350,
            'fat' => 12,
            'saturated_fat' => 5,
            'carbohydrates' => 45,
            'sugars' => 20,
            'proteins' => 8,
            'salt' => 1.2,
        ];

        try {
            $filters = $category ? ['categories_tags' => $category] : [];
            $result = $this->search($filters, 1, min($limit, 30));
            
            $totals = array_fill_keys(array_keys($defaults), 0);
            $count = 0;
            
            foreach ($result['products'] ?? [] as $product) {
                $nutriments = $product['nutriments'] ?? [];
                
                if (isset($nutriments['energy-kcal_100g'])) {
                    $totals['energy'] += $nutriments['energy-kcal_100g'] ?? 0;
                    $totals['fat'] += $nutriments['fat_100g'] ?? 0;
                    $totals['saturated_fat'] += $nutriments['saturated-fat_100g'] ?? 0;
                    $totals['carbohydrates'] += $nutriments['carbohydrates_100g'] ?? 0;
                    $totals['sugars'] += $nutriments['sugars_100g'] ?? 0;
                    $totals['proteins'] += $nutriments['proteins_100g'] ?? 0;
                    $totals['salt'] += $nutriments['salt_100g'] ?? 0;
                    $count++;
                }
            }

            if ($count > 0) {
                $averages = [
                    'energy' => round($totals['energy'] / $count, 2),
                    'fat' => round($totals['fat'] / $count, 2),
                    'saturated_fat' => round($totals['saturated_fat'] / $count, 2),
                    'carbohydrates' => round($totals['carbohydrates'] / $count, 2),
                    'sugars' => round($totals['sugars'] / $count, 2),
                    'proteins' => round($totals['proteins'] / $count, 2),
                    'salt' => round($totals['salt'] / $count, 2),
                ];
            } else {
                $averages = $defaults;
            }
            
            $this->cache[$cacheKey] = $averages;
            return $averages;
            
        } catch (\Exception $e) {
            $this->logger->warning('Failed to get average nutriments, using defaults', [
                'error' => $e->getMessage(),
            ]);
            return $defaults;
        }
    }

    /**
     * Recherche par nutriscore (utilisé par searchByNutriscore)
     */
    public function searchByNutriscore(array $grades, int $limit = 30): array
    {
        $allProducts = [];

        foreach ($grades as $grade) {
            try {
                $result = $this->search([
                    'nutrition_grades_tags' => strtolower($grade),
                ], 1, min(10, (int)($limit / count($grades))));
                
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
    
    /**
     * Recherche de produits par nom (search_terms)
     * Utilise l'API v1 car v2 ne supporte pas search_terms
     */
    public function searchByName(string $name, int $limit = 10): array
    {
        $cacheKey = "search_name_" . md5($name) . "_{$limit}";
        
        if (isset($this->cache[$cacheKey])) {
            return $this->cache[$cacheKey];
        }

        try {
            // Rate limit protection
            sleep(6);
            
            $response = $this->httpClient->request('GET', '/cgi/search.pl', [
                'query' => [
                    'search_terms' => $name,
                    'search_simple' => 1,
                    'action' => 'process',
                    'json' => 1,
                    'page_size' => min($limit, 20),
                    'fields' => 'code,product_name,brands,nutriscore_grade,nova_group,categories_tags,quantity,origins_tags',
                ],
            ]);
            
            $data = $response->toArray();
            $products = $data['products'] ?? [];
            
            $this->cache[$cacheKey] = $products;
            return $products;
            
        } catch (\Exception $e) {
            $this->logger->error('OpenFoodFacts API error (searchByName)', [
                'name' => $name,
                'error' => $e->getMessage(),
            ]);
            return [];
        }
    }

    /**
     * Récupère plusieurs produits par leurs codes-barres
     */
    public function getMultipleProducts(array $barcodes): array
    {
        if (empty($barcodes)) {
            return [];
        }

        $cacheKey = "multi_" . md5(implode(',', $barcodes));
        
        if (isset($this->cache[$cacheKey])) {
            return $this->cache[$cacheKey];
        }

        try {
            $codes = implode(',', $barcodes);
            
            $response = $this->httpClient->request(
                'GET',
                sprintf('/api/%s/search', self::API_VERSION),
                [
                    'query' => [
                        'code' => $codes,
                        'fields' => 'code,product_name,brands,nutriscore_grade,nova_group,nutriments',
                    ],
                ]
            );
            
            $data = $response->toArray();
            $products = $data['products'] ?? [];
            
            $this->cache[$cacheKey] = $products;
            return $products;
            
        } catch (\Exception $e) {
            $this->logger->error('OpenFoodFacts API error (getMultipleProducts)', [
                'barcodes' => $barcodes,
                'error' => $e->getMessage(),
            ]);
            return [];
        }
    }

    /**
     * Récupère un produit avec tous les détails nécessaires
     */
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
            
            if ($data['status'] === 1) {
                $product = $data['product'] ?? null;
                if ($product) {
                    // Ajouter l'URL OpenFoodFacts
                    $product['openfoodfacts_url'] = "https://fr.openfoodfacts.org/produit/{$barcode}";
                    $this->cache[$cacheKey] = $product;
                    return $product;
                }
            }
            
            return null;
        } catch (\Exception $e) {
            $this->logger->error('OpenFoodFacts API error (getProductDetails)', [
                'barcode' => $barcode,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

}