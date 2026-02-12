<?php

declare(strict_types=1);

namespace Infrastructure\Api;

use OpenFoodFacts\Api;

final readonly class OpenFoodFactsService
{
    private Api $api;

    public function __construct()
    {
        $this->api = new Api('food', 'fr');
    }

    /**
     * Récupère un produit par son barcode
     */
    public function getProduct(string $barcode): ?array
    {
        try {
            $product = $this->api->getProduct($barcode);
            return $product ? (array) $product : null;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Recherche des produits
     */
    public function search(string $query, int $page = 1, int $pageSize = 20): array
    {
        try {
            $result = $this->api->search($query, $page, $pageSize);
            return (array) $result;
        } catch (\Exception $e) {
            return ['products' => [], 'count' => 0];
        }
    }

    /**
     * Récupère les statistiques par Nutriscore
     */
    public function getNutriscoreStats(int $limit = 100): array
    {
        $stats = [
            'a' => 0,
            'b' => 0,
            'c' => 0,
            'd' => 0,
            'e' => 0,
        ];

        $result = $this->search('', 1, $limit);
        
        if (isset($result['products'])) {
            foreach ($result['products'] as $product) {
                $productArray = (array) $product;
                $grade = strtolower($productArray['nutriscore_grade'] ?? '');
                if (isset($stats[$grade])) {
                    $stats[$grade]++;
                }
            }
        }

        return $stats;
    }

    /**
     * Récupère les statistiques par NOVA group
     */
    public function getNovaGroupStats(int $limit = 100): array
    {
        $stats = [
            '1' => 0,
            '2' => 0,
            '3' => 0,
            '4' => 0,
        ];

        $result = $this->search('', 1, $limit);
        
        if (isset($result['products'])) {
            foreach ($result['products'] as $product) {
                $productArray = (array) $product;
                $novaGroup = (string)($productArray['nova_group'] ?? '0');
                if (isset($stats[$novaGroup])) {
                    $stats[$novaGroup]++;
                }
            }
        }

        return $stats;
    }

    /**
     * Récupère les produits par catégorie
     */
    public function searchByCategory(string $category, int $limit = 50): array
    {
        $result = $this->search("categories:{$category}", 1, $limit);
        return $result['products'] ?? [];
    }

    /**
     * Récupère les statistiques de nutriments moyens
     */
    public function getAverageNutriments(?string $category = null, int $limit = 100): array
    {
        $query = $category ? "categories:{$category}" : '';
        $result = $this->search($query, 1, $limit);
        
        $totals = [
            'energy' => 0,
            'fat' => 0,
            'saturated_fat' => 0,
            'carbohydrates' => 0,
            'sugars' => 0,
            'proteins' => 0,
            'salt' => 0,
        ];

        $count = 0;
        $products = $result['products'] ?? [];

        foreach ($products as $product) {
            $productArray = (array) $product;
            $nutriments = $productArray['nutriments'] ?? [];
            $nutrimentsArray = (array) $nutriments;
            
            if (isset($nutrimentsArray['energy-kcal_100g'])) {
                $totals['energy'] += $nutrimentsArray['energy-kcal_100g'];
                $totals['fat'] += $nutrimentsArray['fat_100g'] ?? 0;
                $totals['saturated_fat'] += $nutrimentsArray['saturated-fat_100g'] ?? 0;
                $totals['carbohydrates'] += $nutrimentsArray['carbohydrates_100g'] ?? 0;
                $totals['sugars'] += $nutrimentsArray['sugars_100g'] ?? 0;
                $totals['proteins'] += $nutrimentsArray['proteins_100g'] ?? 0;
                $totals['salt'] += $nutrimentsArray['salt_100g'] ?? 0;
                $count++;
            }
        }

        if ($count === 0) {
            return $totals;
        }

        return [
            'energy' => round($totals['energy'] / $count, 2),
            'fat' => round($totals['fat'] / $count, 2),
            'saturated_fat' => round($totals['saturated_fat'] / $count, 2),
            'carbohydrates' => round($totals['carbohydrates'] / $count, 2),
            'sugars' => round($totals['sugars'] / $count, 2),
            'proteins' => round($totals['proteins'] / $count, 2),
            'salt' => round($totals['salt'] / $count, 2),
        ];
    }

    /**
     * Récupère des produits par Nutriscore
     */
    public function searchByNutriscore(array $grades, int $limit = 100): array
    {
        $allProducts = [];

        foreach ($grades as $grade) {
            $result = $this->search("nutrition_grades:{$grade}", 1, (int)($limit / count($grades)));
            
            if (isset($result['products'])) {
                $allProducts = array_merge($allProducts, $result['products']);
            }
        }

        return $allProducts;
    }
}