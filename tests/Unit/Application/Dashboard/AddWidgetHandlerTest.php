<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Dashboard;

use Application\Dashboard\Command\AddWidgetCommand;
use Application\Dashboard\Handler\AddWidgetHandler;
use Domain\Dashboard\Entity\Dashboard;
use Domain\Dashboard\Entity\Widget;
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
        $dashboard = new Dashboard(Uuid::v4(), $userId);

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
            type: WidgetType::PRODUCT_SEARCH,
            row: 1,
            column: 1,
            configuration: ['barcode' => '123456']
        );

        $result = $this->handler->handle($command);

        $this->assertTrue($result->isSuccess());
        $this->assertNotNull($result->getWidgetId());
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
            ->with($this->callback(function ($dashboard) use ($userId) {
                return $dashboard instanceof Dashboard
                    && $dashboard->getUserId()->equals($userId);
            }));

        $command = new AddWidgetCommand(
            userId: $userId,
            type: WidgetType::SHOPPING_LIST,
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
        $dashboard = new Dashboard(Uuid::v4(), $userId);

        // Ajouter un widget à la position (1,1)
        $existingWidget = new Widget(
            id: Uuid::v4(),
            dashboard: $dashboard,
            type: WidgetType::NUTRITION_PIE, 
            row: 1,
            column: 1,
            configuration: []
        );
        $dashboard->addWidget($existingWidget);

        $this->dashboardRepository
            ->expects($this->once())
            ->method('findByUserId')
            ->with($userId)
            ->willReturn($dashboard);

        // Tentative d'ajout à la même position
        $command = new AddWidgetCommand(
            userId: $userId,
            type: WidgetType::PRODUCT_SEARCH,
            row: 1,
            column: 1,
            configuration: []
        );

        $result = $this->handler->handle($command);

        $this->assertFalse($result->isSuccess());
        $this->assertEquals('Position déjà occupée', $result->getErrorMessage());
    }
}