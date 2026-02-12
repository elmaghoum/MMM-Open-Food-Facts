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
}