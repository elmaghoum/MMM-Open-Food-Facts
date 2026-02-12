<?php

declare(strict_types=1);

namespace Application\Dashboard\Handler;

use Application\Dashboard\Command\MoveWidgetCommand;
use Application\Dashboard\DTO\MoveWidgetResult;
use Domain\Dashboard\Repository\DashboardRepositoryInterface;

final readonly class MoveWidgetHandler
{
    public function __construct(
        private DashboardRepositoryInterface $dashboardRepository,
    ) {
    }

    public function handle(MoveWidgetCommand $command): MoveWidgetResult
    {
        try {
            $dashboard = $this->dashboardRepository->findById($command->dashboardId);

            if (!$dashboard) {
                return MoveWidgetResult::failure('Dashboard not found');
            }

            $dashboard->moveWidget(
                widgetId: $command->widgetId,
                newRow: $command->newRow,
                newColumn: $command->newColumn
            );

            $this->dashboardRepository->save($dashboard);

            return MoveWidgetResult::success();

        } catch (\DomainException $e) {
            return MoveWidgetResult::failure($e->getMessage());
        }
    }
}