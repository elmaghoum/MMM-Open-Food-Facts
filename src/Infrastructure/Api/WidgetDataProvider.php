<?php

declare(strict_types=1);

namespace Infrastructure\Api;

use Domain\Dashboard\ValueObject\WidgetType;

final readonly class WidgetDataProvider
{
    public function __construct(
        private OpenFoodFactsService $openFoodFactsService,
    ) {
    }

    public function getDataForWidget(WidgetType $type, array $configuration): array
    {
        return match ($type) {
            WidgetType::PRODUCT_SEARCH => $this->generateProductSearchData($configuration),
            WidgetType::QUICK_BARCODE_SEARCH => $this->generateQuickBarcodeSearchData($configuration),
            WidgetType::SUGAR_SALT_COMPARISON => $this->generateSugarSaltComparisonData($configuration),
            WidgetType::NUTRISCORE_COMPARISON => $this->generateNutriscoreComparisonData($configuration),
            WidgetType::NOVA_COMPARISON => $this->generateNovaComparisonData($configuration),
            WidgetType::NUTRITION_PIE => $this->generateNutritionPieData($configuration),
            WidgetType::SHOPPING_LIST => $this->generateShoppingListData($configuration),
        };
    }

    private function generateProductSearchData(array $config): array
    {
        $barcode = $config['barcode'] ?? null;
        
        if (!$barcode) {
            return ['error' => 'No barcode provided'];
        }

        $product = $this->openFoodFactsService->getProductDetails($barcode);
        
        if (!$product) {
            return ['error' => 'Une erreur est survenue lors de la récupération du produit. Rafraîchissez la page.'];
        }

        return [
            'type' => 'product_info',
            'product' => [
                'name' => $product['product_name'] ?? 'N/A',
                'brands' => $product['brands'] ?? 'N/A',
                'barcode' => $product['code'] ?? $barcode,
                'nutriscore' => strtoupper($product['nutriscore_grade'] ?? 'N/A'),
                'nova' => $product['nova_group'] ?? 'N/A',
                'categories' => implode(', ', array_slice($product['categories_tags'] ?? [], 0, 3)),
                'quantity' => $product['quantity'] ?? 'N/A',
                'origins' => implode(', ', $product['origins_tags'] ?? ['N/A']),
                'url' => $product['openfoodfacts_url'] ?? '#',
            ],
        ];
    }

    private function generateSugarSaltComparisonData(array $config): array
    {
        $barcodes = $config['barcodes'] ?? [];
        
        if (empty($barcodes) || count($barcodes) > 5) {
            return ['error' => 'Provide between 1 and 5 barcodes'];
        }

        $products = $this->openFoodFactsService->getMultipleProducts($barcodes);
        
        $labels = [];
        $sugarData = [];
        $saltData = [];

        foreach ($products as $product) {
            $labels[] = $product['product_name'] ?? 'Unknown';
            $sugarData[] = $product['nutriments']['sugars_100g'] ?? 0;
            $saltData[] = $product['nutriments']['salt_100g'] ?? 0;
        }

        return [
            'type' => 'bar',
            'data' => [
                'labels' => $labels,
                'datasets' => [
                    [
                        'label' => 'Sucre (g/100g)',
                        'data' => $sugarData,
                        'backgroundColor' => '#FF6384',
                    ],
                    [
                        'label' => 'Sel (g/100g)',
                        'data' => $saltData,
                        'backgroundColor' => '#36A2EB',
                    ],
                ],
            ],
        ];
    }

    private function generateNutriscoreComparisonData(array $config): array
    {
        $barcodes = $config['barcodes'] ?? [];
        
        if (empty($barcodes) || count($barcodes) > 5) {
            return ['error' => 'Provide between 1 and 5 barcodes'];
        }

        $products = $this->openFoodFactsService->getMultipleProducts($barcodes);
        
        $comparison = [];
        foreach ($products as $product) {
            $comparison[] = [
                'name' => $product['product_name'] ?? 'Unknown',
                'brands' => $product['brands'] ?? 'N/A',
                'barcode' => $product['code'] ?? 'N/A',
                'nutriscore' => strtoupper($product['nutriscore_grade'] ?? 'N/A'),
                'nutriscore_color' => $this->getNutriscoreColor($product['nutriscore_grade'] ?? ''),
            ];
        }

        return [
            'type' => 'nutriscore_comparison',
            'products' => $comparison,
        ];
    }

    private function generateNovaComparisonData(array $config): array
    {
        $barcodes = $config['barcodes'] ?? [];
        
        if (empty($barcodes) || count($barcodes) > 5) {
            return ['error' => 'Provide between 1 and 5 barcodes'];
        }

        $products = $this->openFoodFactsService->getMultipleProducts($barcodes);
        
        $comparison = [];
        foreach ($products as $product) {
            $comparison[] = [
                'name' => $product['product_name'] ?? 'Unknown',
                'brands' => $product['brands'] ?? 'N/A',
                'barcode' => $product['code'] ?? 'N/A',
                'nova' => $product['nova_group'] ?? 'N/A',
                'nova_color' => $this->getNovaColor($product['nova_group'] ?? 0),
            ];
        }

        return [
            'type' => 'nova_comparison',
            'products' => $comparison,
        ];
    }

    private function generateNutritionPieData(array $config): array
    {
        $barcode = $config['barcode'] ?? null;
        
        if (!$barcode) {
            return ['error' => 'No barcode provided'];
        }

        $product = $this->openFoodFactsService->getProductDetails($barcode);
        
        if (!$product) {
            return ['error' => 'Une erreur est survenue lors de la récupération du produit. Rafraîchissez la page.'];
        }

        $nutriments = $product['nutriments'] ?? [];

        return [
            'type' => 'pie',
            'data' => [
                'labels' => ['Protéines', 'Glucides', 'Lipides', 'Sel'],
                'datasets' => [
                    [
                        'data' => [
                            $nutriments['proteins_100g'] ?? 0,
                            $nutriments['carbohydrates_100g'] ?? 0,
                            $nutriments['fat_100g'] ?? 0,
                            ($nutriments['salt_100g'] ?? 0) * 10, // Multiplier par 10 pour visibilité
                        ],
                        'backgroundColor' => ['#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0'],
                    ],
                ],
            ],
            'product_name' => $product['product_name'] ?? 'Unknown',
        ];
    }

    private function getNutriscoreColor(string $grade): string
    {
        return match (strtolower($grade)) {
            'a' => '#038141',
            'b' => '#85BB2F',
            'c' => '#FECB02',
            'd' => '#EE8100',
            'e' => '#E63E11',
            default => '#CCCCCC',
        };
    }

    private function getNovaColor(int $nova): string
    {
        return match ($nova) {
            1 => '#4CAF50',
            2 => '#FFC107',
            3 => '#FF9800',
            4 => '#F44336',
            default => '#CCCCCC',
        };
    }
    private function generateQuickBarcodeSearchData(array $config): array
    {
        $barcode = $config['barcode'] ?? null;
        
        if (!$barcode) {
            return ['type' => 'quick_barcode_empty'];
        }

        $product = $this->openFoodFactsService->getProductByBarcode($barcode);
        
        if (!$product) {
            return ['error' => 'Une erreur est survenue lors de la récupération du produit. Rafraîchissez la page.'];
        }

        return [
            'type' => 'product_info',
            'product' => [
                'name' => $product['product_name'] ?? 'N/A',
                'brands' => $product['brands'] ?? 'N/A',
                'barcode' => $product['code'] ?? $barcode,
                'nutriscore' => strtoupper($product['nutriscore_grade'] ?? 'N/A'),
                'nova' => $product['nova_group'] ?? 'N/A',
                'categories' => implode(', ', array_slice($product['categories_tags'] ?? [], 0, 3)),
                'quantity' => $product['quantity'] ?? 'N/A',
                'origins' => implode(', ', $product['origins_tags'] ?? ['N/A']),
                'url' => $product['openfoodfacts_url'] ?? '#',
            ],
        ];
    }

    private function generateShoppingListData(array $config): array
    {
        $barcodes = $config['barcodes'] ?? [];
        
        if (empty($barcodes)) {
            return ['type' => 'shopping_list_empty'];
        }

        $products = [];
        foreach ($barcodes as $barcode) {
            $product = $this->openFoodFactsService->getProductByBarcode($barcode);
            if ($product) {
                $products[] = [
                    'name' => $product['product_name'] ?? 'Unknown',
                    'brands' => $product['brands'] ?? 'N/A',
                    'quantity' => $product['quantity'] ?? 'N/A',
                    'barcode' => $product['code'] ?? $barcode,
                    'url' => "https://fr.openfoodfacts.org/produit/{$barcode}",
                ];
            }
        }

        return [
            'type' => 'shopping_list',
            'products' => $products,
        ];
    }
}