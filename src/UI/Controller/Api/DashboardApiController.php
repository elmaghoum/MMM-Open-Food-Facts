<?php

declare(strict_types=1);

namespace UI\Controller\Api;

use Domain\Dashboard\Repository\DashboardRepositoryInterface;
use Domain\Identity\Repository\UserRepositoryInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api')]
final class DashboardApiController extends AbstractController
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
        private readonly DashboardRepositoryInterface $dashboardRepository,
    ) {
    }

    #[Route('/dashboard/{email}', name: 'api_dashboard_get', methods: ['GET'])]
    public function getDashboard(string $email): JsonResponse
    {
        $user = $this->userRepository->findByEmail($email);

        if (!$user) {
            return $this->json([
                'error' => 'User not found',
                'email' => $email,
            ], 404);
        }

        $dashboard = $this->dashboardRepository->findByUserId($user->getId());

        if (!$dashboard) {
            return $this->json([
                'error' => 'Dashboard not found',
                'email' => $email,
            ], 404);
        }

        $widgetsData = [];
        foreach ($dashboard->getWidgets() as $widget) {
            $widgetsData[] = [
                'id' => $widget->getId()->toRfc4122(),
                'type' => $widget->getType()->value,
                'row' => $widget->getRow(),
                'column' => $widget->getColumn(),
                'configuration' => $widget->getConfiguration(),
            ];
        }

        return $this->json([
            'success' => true,
            'user' => [
                'id' => $user->getId()->toRfc4122(),
                'email' => $user->getEmail(),
                'roles' => $user->getRoles(),
                'created_at' => $user->getCreatedAt()->format('c'),
            ],
            'dashboard' => [
                'id' => $dashboard->getId()->toRfc4122(),
                'widgets_count' => count($widgetsData),
                'widgets' => $widgetsData,
            ],
        ]);
    }
}