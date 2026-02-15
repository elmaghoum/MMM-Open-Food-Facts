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
}