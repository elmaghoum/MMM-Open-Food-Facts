<?php

declare(strict_types=1);

namespace UI\Controller;

use Application\Dashboard\Command\AddWidgetCommand;
use Application\Dashboard\Handler\AddWidgetHandler;
use Domain\Dashboard\Repository\DashboardRepositoryInterface;
use Domain\Dashboard\ValueObject\WidgetType;
use Infrastructure\Api\WidgetDataProvider;
use Infrastructure\Security\UserAdapter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
final class DashboardController extends AbstractController
{
    public function __construct(
        private readonly DashboardRepositoryInterface $dashboardRepository,
        private readonly AddWidgetHandler $addWidgetHandler,
        private readonly WidgetDataProvider $widgetDataProvider,
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

        // Trouver le widget
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

        $data = $this->widgetDataProvider->getDataForWidget(
            $widget->getType(),
            $widget->getConfiguration()
        );

        return new JsonResponse($data);
    }
}