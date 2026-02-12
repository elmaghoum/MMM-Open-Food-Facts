<?php

declare(strict_types=1);

namespace Application\Dashboard\Handler;

use Application\Dashboard\Command\AddWidgetCommand;
use Application\Dashboard\DTO\AddWidgetResult;
use Domain\Dashboard\Entity\Dashboard;
use Domain\Dashboard\Repository\DashboardRepositoryInterface;

final readonly class AddWidgetHandler
{
    public function __construct(
        private DashboardRepositoryInterface $dashboardRepository,
    ) {
    }

    public function handle(AddWidgetCommand $command): AddWidgetResult
    {
        try {
            $dashboard = $this->dashboardRepository->findByUserId($command->userId);

            // Créer le dashboard s'il n'existe pas
            if (!$dashboard) {
                $dashboard = Dashboard::create($command->userId);
            }

            $widget = $dashboard->addWidget(
                type: $command->type,
                row: $command->row,
                column: $command->column,
                configuration: $command->configuration
            );

            $this->dashboardRepository->save($dashboard);

            return AddWidgetResult::success($widget->getId());

        } catch (\DomainException $e) {
            return AddWidgetResult::failure($e->getMessage());
        }
    }
}