<?php

declare(strict_types=1);

namespace UI\Controller;

use Application\Dashboard\Command\AddWidgetCommand;
use Application\Dashboard\Handler\AddWidgetHandler;
use Application\Dashboard\Handler\MoveWidgetHandler;
use Application\Dashboard\Command\MoveWidgetCommand;
use Domain\Dashboard\Repository\DashboardRepositoryInterface;
use Domain\Dashboard\ValueObject\WidgetType;
use Infrastructure\Api\WidgetDataProvider;
use Infrastructure\Api\OpenFoodFactsService;
use Infrastructure\Security\UserAdapter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Uid\Uuid;

#[IsGranted('ROLE_USER')]
final class DashboardController extends AbstractController
{
    public function __construct(
        private readonly DashboardRepositoryInterface $dashboardRepository,
        private readonly AddWidgetHandler $addWidgetHandler,
        private readonly MoveWidgetHandler $moveWidgetHandler,
        private readonly WidgetDataProvider $widgetDataProvider,
        private readonly OpenFoodFactsService $openFoodFactsService,
    ) {
    }

    #[Route('/dashboard', name: 'dashboard')]
    public function index(): Response
    {
        /** @var UserAdapter $user */
        $user = $this->getUser();
        
        $dashboard = $this->dashboardRepository->findByUserId($user->getId());

        return $this->render('dashboard/index.html.twig', [
            'dashboard' => $dashboard,
        ]);
    }

    #[Route('/dashboard/search-products', name: 'dashboard_search_products', methods: ['GET'])]
    public function searchProducts(Request $request): JsonResponse
    {
        $query = $request->query->get('q', '');
        
        if (strlen($query) < 2) {
            return new JsonResponse(['products' => []]);
        }

        $products = $this->openFoodFactsService->searchByName($query, 10);
        
        // Formater les résultats
        $results = array_map(function($product) {
            return [
                'barcode' => $product['code'] ?? '',
                'name' => $product['product_name'] ?? 'Unknown',
                'brands' => $product['brands'] ?? '',
                'nutriscore' => strtoupper($product['nutriscore_grade'] ?? 'N/A'),
            ];
        }, $products);

        return new JsonResponse(['products' => $results]);
    }

    #[Route('/dashboard/widget/add', name: 'dashboard_widget_add', methods: ['POST'])]
    public function addWidget(Request $request): JsonResponse
    {
        /** @var UserAdapter $user */
        $user = $this->getUser();

        $data = json_decode($request->getContent(), true);

        try {
            $command = new AddWidgetCommand(
                userId: $user->getId(),
                type: WidgetType::from($data['type']),
                row: (int)$data['row'],
                column: (int)$data['column'],
                configuration: $data['configuration'] ?? []
            );

            $result = $this->addWidgetHandler->handle($command);

            if ($result->isSuccess()) {
                return new JsonResponse([
                    'success' => true,
                    'widgetId' => $result->getWidgetId()->toRfc4122(),
                ]);
            }

            return new JsonResponse([
                'success' => false,
                'error' => $result->getErrorMessage(),
            ], 400);

        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    #[Route('/dashboard/widget/{widgetId}/data', name: 'dashboard_widget_data', methods: ['GET'])]
    public function getWidgetData(string $widgetId): JsonResponse
    {
        /** @var UserAdapter $user */
        $user = $this->getUser();
        
        $dashboard = $this->dashboardRepository->findByUserId($user->getId());

        if (!$dashboard) {
            return new JsonResponse(['error' => 'Dashboard not found'], 404);
        }

        $widget = null;
        foreach ($dashboard->getWidgets() as $w) {
            if ($w->getId()->toRfc4122() === $widgetId) {
                $widget = $w;
                break;
            }
        }

        if (!$widget) {
            return new JsonResponse(['error' => 'Widget not found'], 404);
        }

        try {
            $data = $this->widgetDataProvider->getDataForWidget(
                $widget->getType(),
                $widget->getConfiguration()
            );

            return new JsonResponse($data);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => $e->getMessage()], 500);
        }
    }

    #[Route('/dashboard/widget/{widgetId}/move', name: 'dashboard_widget_move', methods: ['POST'])]
    public function moveWidget(string $widgetId, Request $request): JsonResponse
    {
        /** @var UserAdapter $user */
        $user = $this->getUser();
        
        $dashboard = $this->dashboardRepository->findByUserId($user->getId());

        if (!$dashboard) {
            return new JsonResponse(['error' => 'Dashboard not found'], 404);
        }

        $data = json_decode($request->getContent(), true);

        try {
            $command = new MoveWidgetCommand(
                dashboardId: $dashboard->getId(),
                widgetId: Uuid::fromString($widgetId),
                newRow: (int)$data['row'],
                newColumn: (int)$data['column']
            );

            $result = $this->moveWidgetHandler->handle($command);

            if ($result->isSuccess()) {
                return new JsonResponse(['success' => true]);
            }

            return new JsonResponse([
                'success' => false,
                'error' => $result->getErrorMessage(),
            ], 400);

        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    #[Route('/dashboard/widget/{widgetId}/remove', name: 'dashboard_widget_remove', methods: ['DELETE'])]
    public function removeWidget(string $widgetId): JsonResponse
    {
        /** @var UserAdapter $user */
        $user = $this->getUser();
        
        $dashboard = $this->dashboardRepository->findByUserId($user->getId());

        if (!$dashboard) {
            return new JsonResponse(['error' => 'Dashboard not found'], 404);
        }

        try {
            $dashboard->removeWidget(Uuid::fromString($widgetId));
            $this->dashboardRepository->save($dashboard);

            return new JsonResponse(['success' => true]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    #[Route('/dashboard/widget/{widgetId}/update-config', name: 'dashboard_widget_update_config', methods: ['POST'])]
    public function updateWidgetConfig(string $widgetId, Request $request): JsonResponse
    {
        /** @var UserAdapter $user */
        $user = $this->getUser();
        
        $dashboard = $this->dashboardRepository->findByUserId($user->getId());

        if (!$dashboard) {
            return new JsonResponse(['error' => 'Dashboard not found'], 404);
        }

        $widget = null;
        foreach ($dashboard->getWidgets() as $w) {
            if ($w->getId()->toRfc4122() === $widgetId) {
                $widget = $w;
                break;
            }
        }

        if (!$widget) {
            return new JsonResponse(['error' => 'Widget not found'], 404);
        }

        $data = json_decode($request->getContent(), true);

        try {
            $widget->updateConfiguration($data['configuration'] ?? []);
            $this->dashboardRepository->save($dashboard);

            return new JsonResponse(['success' => true]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'error' => $e->getMessage(),
            ], 400);
        }
    }
    
    #[Route('/dashboard/shopping-list/add/{barcode}', name: 'dashboard_shopping_list_add', methods: ['POST'])]
    public function addToShoppingList(string $barcode): JsonResponse
    {
        /** @var UserAdapter $user */
        $user = $this->getUser();
        
        $dashboard = $this->dashboardRepository->findByUserId($user->getId());

        if (!$dashboard) {
            return new JsonResponse(['success' => false, 'error' => 'Dashboard not found'], 404);
        }

        // Vérifier qu'il existe un widget "shopping_list"
        $shoppingListWidget = null;
        foreach ($dashboard->getWidgets() as $widget) {
            if ($widget->getType() === WidgetType::SHOPPING_LIST) {
                $shoppingListWidget = $widget;
                break;
            }
        }

        if (!$shoppingListWidget) {
            return new JsonResponse([
                'success' => false,
                'error' => 'Vous n\'avez pas de liste de course. Ajoutez d\'abord le widget "Ma Liste de Course".'
            ], 400);
        }

        // Récupérer la config actuelle
        $config = $shoppingListWidget->getConfiguration();
        $barcodes = $config['barcodes'] ?? [];

        // Vérifier la limite
        if (count($barcodes) >= 20) {
            return new JsonResponse([
                'success' => false,
                'error' => 'Vous avez atteint la limite de 20 produits pour cette version de démonstration.'
            ], 400);
        }

        // Vérifier les doublons
        if (in_array($barcode, $barcodes)) {
            return new JsonResponse([
                'success' => false,
                'error' => 'Ce produit est déjà dans votre liste.'
            ], 400);
        }

        // Ajouter le produit
        $barcodes[] = $barcode;
        $config['barcodes'] = $barcodes;
        
        $shoppingListWidget->updateConfiguration($config);
        $this->dashboardRepository->save($dashboard);

        return new JsonResponse([
            'success' => true,
            'message' => 'Produit ajouté à votre liste de course !',
            'count' => count($barcodes)
        ]);
    }

    #[Route('/dashboard/shopping-list/remove/{barcode}', name: 'dashboard_shopping_list_remove', methods: ['DELETE'])]
    public function removeFromShoppingList(string $barcode): JsonResponse
    {
        /** @var UserAdapter $user */
        $user = $this->getUser();
        
        $dashboard = $this->dashboardRepository->findByUserId($user->getId());

        if (!$dashboard) {
            return new JsonResponse(['success' => false, 'error' => 'Dashboard not found'], 404);
        }

        // Trouver le widget shopping_list
        $shoppingListWidget = null;
        foreach ($dashboard->getWidgets() as $widget) {
            if ($widget->getType() === WidgetType::SHOPPING_LIST) {
                $shoppingListWidget = $widget;
                break;
            }
        }

        if (!$shoppingListWidget) {
            return new JsonResponse(['success' => false, 'error' => 'Shopping list widget not found'], 404);
        }

        // Retirer le produit
        $config = $shoppingListWidget->getConfiguration();
        $barcodes = $config['barcodes'] ?? [];
        $barcodes = array_values(array_filter($barcodes, fn($b) => $b !== $barcode));
        
        $config['barcodes'] = $barcodes;
        $shoppingListWidget->updateConfiguration($config);
        $this->dashboardRepository->save($dashboard);

        return new JsonResponse([
            'success' => true,
            'message' => 'Produit retiré de votre liste.',
            'count' => count($barcodes)
        ]);
    }

    #[Route('/dashboard/shopping-list/clear', name: 'dashboard_shopping_list_clear', methods: ['DELETE'])]
    public function clearShoppingList(): JsonResponse
    {
        /** @var UserAdapter $user */
        $user = $this->getUser();
        
        $dashboard = $this->dashboardRepository->findByUserId($user->getId());

        if (!$dashboard) {
            return new JsonResponse(['success' => false, 'error' => 'Dashboard not found'], 404);
        }

        // Trouver le widget shopping_list
        $shoppingListWidget = null;
        foreach ($dashboard->getWidgets() as $widget) {
            if ($widget->getType() === WidgetType::SHOPPING_LIST) {
                $shoppingListWidget = $widget;
                break;
            }
        }

        if (!$shoppingListWidget) {
            return new JsonResponse(['success' => false, 'error' => 'Shopping list widget not found'], 404);
        }

        // Vider la liste
        $config = $shoppingListWidget->getConfiguration();
        $config['barcodes'] = [];
        
        $shoppingListWidget->updateConfiguration($config);
        $this->dashboardRepository->save($dashboard);

        return new JsonResponse([
            'success' => true,
            'message' => 'Liste de course vidée.',
            'count' => 0
        ]);
    }
}