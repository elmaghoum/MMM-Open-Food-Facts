<?php

declare(strict_types=1);

namespace Application\Dashboard\Handler;

use Application\Dashboard\Command\AddWidgetCommand;
use Application\Dashboard\DTO\AddWidgetResult;
use Domain\Dashboard\Entity\Dashboard;
use Domain\Dashboard\Entity\Widget;
use Domain\Dashboard\Repository\DashboardRepositoryInterface;
use Domain\Dashboard\ValueObject\WidgetType;
use Symfony\Component\Uid\Uuid;

final readonly class AddWidgetHandler
{
    public function __construct(
        private DashboardRepositoryInterface $dashboardRepository,
    ) {
    }

    public function handle(AddWidgetCommand $command): AddWidgetResult
    {
        $dashboard = $this->dashboardRepository->findByUserId($command->userId);

        if (!$dashboard) {
            $dashboard = new Dashboard(Uuid::v4(), $command->userId);
        }

        try {
            if ($command->type === WidgetType::SHOPPING_LIST) {
                foreach ($dashboard->getWidgets() as $existingWidget) {
                    if ($existingWidget->getType() === WidgetType::SHOPPING_LIST) {
                        return AddWidgetResult::failure('Vous avez déjà une liste de course. Vous ne pouvez en avoir qu\'une seule.');
                    }
                }
            }

            // Vérifier si la position est déjà occupée
            foreach ($dashboard->getWidgets() as $existingWidget) {
                if ($existingWidget->getRow() === $command->row && 
                    $existingWidget->getColumn() === $command->column) {
                    return AddWidgetResult::failure('Position déjà occupée');
                }
            }

            $widget = new Widget(
                id: Uuid::v4(),
                dashboard: $dashboard,
                type: $command->type,
                row: $command->row,
                column: $command->column,
                configuration: $command->configuration
            );

            $dashboard->addWidget($widget);
            $this->dashboardRepository->save($dashboard);

            return AddWidgetResult::success($widget->getId());
        } catch (\Exception $e) {
            return AddWidgetResult::failure($e->getMessage());
        }
    }
}