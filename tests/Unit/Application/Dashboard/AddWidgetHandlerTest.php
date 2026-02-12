<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Dashboard;

use Application\Dashboard\Command\AddWidgetCommand;
use Application\Dashboard\Handler\AddWidgetHandler;
use Domain\Dashboard\Entity\Dashboard;
use Domain\Dashboard\Repository\DashboardRepositoryInterface;
use Domain\Dashboard\ValueObject\WidgetType;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

final class AddWidgetHandlerTest extends TestCase
{
    private DashboardRepositoryInterface $dashboardRepository;
    private AddWidgetHandler $handler;

    protected function setUp(): void
    {
        $this->dashboardRepository = $this->createMock(DashboardRepositoryInterface::class);
        $this->handler = new AddWidgetHandler($this->dashboardRepository);
    }

    public function testWidgetCanBeAdded(): void
    {
        $userId = Uuid::v4();
        $dashboard = Dashboard::create($userId);

        $this->dashboardRepository
            ->expects($this->once())
            ->method('findByUserId')
            ->with($userId)
            ->willReturn($dashboard);

        $this->dashboardRepository
            ->expects($this->once())
            ->method('save')
            ->with($dashboard);

        $command = new AddWidgetCommand(
            userId: $userId,
            type: WidgetType::PIE_CHART,
            row: 1,
            column: 1,
            configuration: ['data' => 'nutriscore']
        );

        $result = $this->handler->handle($command);

        $this->assertTrue($result->isSuccess());
        $this->assertNotNull($result->getWidgetId());
        $this->assertCount(1, $dashboard->getWidgets());
    }

    public function testDashboardIsCreatedIfNotExists(): void
    {
        $userId = Uuid::v4();

        $this->dashboardRepository
            ->expects($this->once())
            ->method('findByUserId')
            ->with($userId)
            ->willReturn(null);

        $this->dashboardRepository
            ->expects($this->once())
            ->method('save')
            ->with($this->isInstanceOf(Dashboard::class));

        $command = new AddWidgetCommand(
            userId: $userId,
            type: WidgetType::BAR_CHART,
            row: 1,
            column: 1,
            configuration: []
        );

        $result = $this->handler->handle($command);

        $this->assertTrue($result->isSuccess());
    }

    public function testCannotAddWidgetAtOccupiedPosition(): void
    {
        $userId = Uuid::v4();
        $dashboard = Dashboard::create($userId);
        $dashboard->addWidget(WidgetType::PIE_CHART, 1, 1, []);

        $this->dashboardRepository
            ->expects($this->once())
            ->method('findByUserId')
            ->willReturn($dashboard);

        $command = new AddWidgetCommand(
            userId: $userId,
            type: WidgetType::BAR_CHART,
            row: 1,
            column: 1, // Même position !
            configuration: []
        );

        $result = $this->handler->handle($command);

        $this->assertFalse($result->isSuccess());
        $this->assertStringContainsString('already occupied', $result->getErrorMessage());
    }
}