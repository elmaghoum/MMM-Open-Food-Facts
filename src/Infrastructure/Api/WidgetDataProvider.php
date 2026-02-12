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

    /**
     * Génère les données pour un widget selon son type et sa configuration
     */
    public function getDataForWidget(WidgetType $type, array $configuration): array
    {
        return match ($type) {
            WidgetType::PIE_CHART, WidgetType::DOUGHNUT_CHART => $this->generatePieData($configuration),
            WidgetType::BAR_CHART => $this->generateBarData($configuration),
            WidgetType::LINE_CHART => $this->generateLineData($configuration),
            WidgetType::RADAR_CHART => $this->generateRadarData($configuration),
            WidgetType::POLAR_AREA_CHART => $this->generatePolarAreaData($configuration),
            WidgetType::MIXED_CHART => $this->generateMixedData($configuration),
        };
    }

    private function generatePieData(array $config): array
    {
        $dataType = $config['data'] ?? 'nutriscore';

        if ($dataType === 'nutriscore') {
            $stats = $this->openFoodFactsService->getNutriscoreStats(); 
            
            return [
                'labels' => ['A', 'B', 'C', 'D', 'E'],
                'datasets' => [
                    [
                        'data' => array_values($stats),
                        'backgroundColor' => ['#038141', '#85BB2F', '#FECB02', '#EE8100', '#E63E11'],
                    ],
                ],
            ];
        }

        if ($dataType === 'nova') {
            $stats = $this->openFoodFactsService->getNovaGroupStats(); 
            
            return [
                'labels' => ['Group 1', 'Group 2', 'Group 3', 'Group 4'],
                'datasets' => [
                    [
                        'data' => array_values($stats),
                        'backgroundColor' => ['#4CAF50', '#FFC107', '#FF9800', '#F44336'],
                    ],
                ],
            ];
        }

        return ['labels' => [], 'datasets' => []];
    }

    private function generateBarData(array $config): array
    {
        $category = $config['category'] ?? null;
        $nutriments = $this->openFoodFactsService->getAverageNutriments($category);

        return [
            'labels' => ['Energy', 'Fat', 'Carbs', 'Proteins', 'Salt'],
            'datasets' => [
                [
                    'label' => 'Average per 100g',
                    'data' => [
                        $nutriments['energy'],
                        $nutriments['fat'],
                        $nutriments['carbohydrates'],
                        $nutriments['proteins'],
                        $nutriments['salt'],
                    ],
                    'backgroundColor' => '#4CAF50',
                ],
            ],
        ];
    }

    private function generateLineData(array $config): array
    {
        return [
            'labels' => ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
            'datasets' => [
                [
                    'label' => 'Products scanned',
                    'data' => [65, 59, 80, 81, 56, 55],
                    'borderColor' => '#4CAF50',
                    'fill' => false,
                ],
            ],
        ];
    }

    private function generateRadarData(array $config): array
    {
        $category = $config['category'] ?? null;
        $nutriments = $this->openFoodFactsService->getAverageNutriments($category); 

        return [
            'labels' => ['Energy', 'Fat', 'Sat. Fat', 'Carbs', 'Sugars', 'Proteins', 'Salt'],
            'datasets' => [
                [
                    'label' => 'Nutriments (avg)',
                    'data' => [
                        $nutriments['energy'] / 10,
                        $nutriments['fat'],
                        $nutriments['saturated_fat'],
                        $nutriments['carbohydrates'],
                        $nutriments['sugars'],
                        $nutriments['proteins'],
                        $nutriments['salt'] * 10,
                    ],
                    'backgroundColor' => 'rgba(76, 175, 80, 0.2)',
                    'borderColor' => '#4CAF50',
                ],
            ],
        ];
    }

    private function generatePolarAreaData(array $config): array
    {
        return $this->generatePieData($config);
    }

    private function generateMixedData(array $config): array
    {
        $nutriments = $this->openFoodFactsService->getAverageNutriments();  

        return [
            'labels' => ['Energy', 'Fat', 'Carbs', 'Proteins'],
            'datasets' => [
                [
                    'type' => 'bar',
                    'label' => 'Average',
                    'data' => [$nutriments['energy'], $nutriments['fat'], $nutriments['carbohydrates'], $nutriments['proteins']],
                    'backgroundColor' => '#4CAF50',
                ],
                [
                    'type' => 'line',
                    'label' => 'Trend',
                    'data' => [$nutriments['energy'] * 0.9, $nutriments['fat'] * 1.1, $nutriments['carbohydrates'] * 0.95, $nutriments['proteins'] * 1.05],
                    'borderColor' => '#FF9800',
                    'fill' => false,
                ],
            ],
        ];
    }
}