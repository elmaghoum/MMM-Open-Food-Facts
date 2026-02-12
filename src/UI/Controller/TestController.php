<?php

namespace UI\Controller;

use Infrastructure\Api\OpenFoodFactsClient;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

class TestController extends AbstractController
{
    #[Route('/test/api', name: 'test_api')]
    public function testApi(OpenFoodFactsClient $client): JsonResponse
    {
        // Test avec un produit Coca-Cola
        $product = $client->getProduct('5449000000996');
        $stats = $client->getNutriscoreStats(50);
        
        return new JsonResponse([
            'product' => $product ? 'OK' : 'NOT FOUND',
            'nutriscore_stats' => $stats,
        ]);
    }
}