<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Dashboard;

use Application\Dashboard\Command\MoveWidgetCommand;
use Application\Dashboard\Handler\MoveWidgetHandler;
use Domain\Dashboard\Entity\Dashboard;
use Domain\Dashboard\Entity\Widget;
use Domain\Dashboard\Repository\DashboardRepositoryInterface;
use Domain\Dashboard\ValueObject\WidgetType;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

final class MoveWidgetHandlerTest extends TestCase
{
    private DashboardRepositoryInterface $dashboardRepository;
    private MoveWidgetHandler $handler;

    protected function setUp(): void
    {
        $this->dashboardRepository = $this->createMock(DashboardRepositoryInterface::class);
        $this->handler = new MoveWidgetHandler($this->dashboardRepository);
    }

    public function testWidgetCanBeMoved(): void
    {
        $dashboardId = Uuid::v4();
        $widgetId = Uuid::v4();
        $dashboard = new Dashboard($dashboardId, Uuid::v4());

        $widget = new Widget(
            id: $widgetId,
            dashboard: $dashboard,
            type: WidgetType::NUTRISCORE_COMPARISON, 
            row: 1,
            column: 1,
            configuration: []
        );
        $dashboard->addWidget($widget);

        $this->dashboardRepository
            ->expects($this->once())
            ->method('findById')
            ->with($dashboardId)
            ->willReturn($dashboard);

        $this->dashboardRepository
            ->expects($this->once())
            ->method('save')
            ->with($dashboard);

        $command = new MoveWidgetCommand(
            dashboardId: $dashboardId,
            widgetId: $widgetId,
            newRow: 2,
            newColumn: 2
        );

        $result = $this->handler->handle($command);

        $this->assertTrue($result->isSuccess());
        $this->assertEquals(2, $widget->getRow());
        $this->assertEquals(2, $widget->getColumn());
    }

    public function testCannotMoveToOccupiedPosition(): void
    {
        $dashboardId = Uuid::v4();
        $dashboard = new Dashboard($dashboardId, Uuid::v4());

        $widget1 = new Widget(
            id: Uuid::v4(),
            dashboard: $dashboard,
            type: WidgetType::NOVA_COMPARISON,
            row: 1,
            column: 1,
            configuration: []
        );
        $dashboard->addWidget($widget1);

        $widget2Id = Uuid::v4();
        $widget2 = new Widget(
            id: $widget2Id,
            dashboard: $dashboard,
            type: WidgetType::SUGAR_SALT_COMPARISON, 
            row: 2,
            column: 1,
            configuration: []
        );
        $dashboard->addWidget($widget2);

        $this->dashboardRepository
            ->expects($this->once())
            ->method('findById')
            ->with($dashboardId)
            ->willReturn($dashboard);

        // Tentative de déplacer widget2 vers la position de widget1
        $command = new MoveWidgetCommand(
            dashboardId: $dashboardId,
            widgetId: $widget2Id,
            newRow: 1,
            newColumn: 1
        );

        $result = $this->handler->handle($command);

        $this->assertFalse($result->isSuccess());
        $this->assertStringContainsString('Position', $result->getErrorMessage());
    }
}