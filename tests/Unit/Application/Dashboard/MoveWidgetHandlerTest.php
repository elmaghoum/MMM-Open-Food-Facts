<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Dashboard;

use Application\Dashboard\Command\MoveWidgetCommand;
use Application\Dashboard\Handler\MoveWidgetHandler;
use Domain\Dashboard\Entity\Dashboard;
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
        $dashboard = Dashboard::create(Uuid::v4());
        $widget = $dashboard->addWidget(WidgetType::PIE_CHART, 1, 1, []);

        $this->dashboardRepository
            ->expects($this->once())
            ->method('findById')
            ->with($dashboard->getId())
            ->willReturn($dashboard);

        $this->dashboardRepository
            ->expects($this->once())
            ->method('save')
            ->with($dashboard);

        $command = new MoveWidgetCommand(
            dashboardId: $dashboard->getId(),
            widgetId: $widget->getId(),
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
        $dashboard = Dashboard::create(Uuid::v4());
        $widget1 = $dashboard->addWidget(WidgetType::PIE_CHART, 1, 1, []);
        $dashboard->addWidget(WidgetType::BAR_CHART, 2, 2, []);

        $this->dashboardRepository
            ->expects($this->once())
            ->method('findById')
            ->willReturn($dashboard);

        $command = new MoveWidgetCommand(
            dashboardId: $dashboard->getId(),
            widgetId: $widget1->getId(),
            newRow: 2,
            newColumn: 2 // Déjà occupé !
        );

        $result = $this->handler->handle($command);

        $this->assertFalse($result->isSuccess());
        $this->assertStringContainsString('already occupied', $result->getErrorMessage());
    }

    public function testDashboardNotFound(): void
    {
        $this->dashboardRepository
            ->expects($this->once())
            ->method('findById')
            ->willReturn(null);

        $command = new MoveWidgetCommand(
            dashboardId: Uuid::v4(),
            widgetId: Uuid::v4(),
            newRow: 1,
            newColumn: 1
        );

        $result = $this->handler->handle($command);

        $this->assertFalse($result->isSuccess());
    }
}