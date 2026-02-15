<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Dashboard;

use Domain\Dashboard\Entity\Dashboard;
use Domain\Dashboard\Entity\Widget;
use Domain\Dashboard\Exception\WidgetNotFoundException;
use Domain\Dashboard\Exception\WidgetPositionAlreadyOccupiedException;
use Domain\Dashboard\ValueObject\WidgetType;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

final class DashboardTest extends TestCase
{
    public function testCreateDashboard(): void
    {
        $userId = Uuid::v4();
        $dashboard = new Dashboard(Uuid::v4(), $userId);

        $this->assertEquals($userId, $dashboard->getUserId());
        $this->assertCount(0, $dashboard->getWidgets());
    }

    public function testAddWidget(): void
    {
        $dashboard = new Dashboard(Uuid::v4(), Uuid::v4());
        
        $widget = new Widget(
            id: Uuid::v4(),
            dashboard: $dashboard,
            type: WidgetType::PRODUCT_SEARCH,
            row: 1,
            column: 1,
            configuration: ['barcode' => '123456']
        );

        $dashboard->addWidget($widget);

        $this->assertCount(1, $dashboard->getWidgets());
    }

    public function testCannotAddWidgetAtOccupiedPosition(): void
    {
        $this->expectException(WidgetPositionAlreadyOccupiedException::class);

        $dashboard = new Dashboard(Uuid::v4(), Uuid::v4());
        
        $widget1 = new Widget(
            id: Uuid::v4(),
            dashboard: $dashboard,
            type: WidgetType::PRODUCT_SEARCH,
            row: 1,
            column: 1,
            configuration: []
        );

        $widget2 = new Widget(
            id: Uuid::v4(),
            dashboard: $dashboard,
            type: WidgetType::NUTRISCORE_COMPARISON,
            row: 1,
            column: 1,
            configuration: []
        );

        $dashboard->addWidget($widget1);
        $dashboard->addWidget($widget2);
    }

    public function testRemoveWidget(): void
    {
        $dashboard = new Dashboard(Uuid::v4(), Uuid::v4());
        
        $widget = new Widget(
            id: Uuid::v4(),
            dashboard: $dashboard,
            type: WidgetType::PRODUCT_SEARCH,
            row: 1,
            column: 1,
            configuration: []
        );

        $dashboard->addWidget($widget);
        $this->assertCount(1, $dashboard->getWidgets());

        $dashboard->removeWidget($widget->getId());
        $this->assertCount(0, $dashboard->getWidgets());
    }

    public function testRemoveNonExistentWidgetThrowsException(): void
    {
        $this->expectException(WidgetNotFoundException::class);

        $dashboard = new Dashboard(Uuid::v4(), Uuid::v4());
        $dashboard->removeWidget(Uuid::v4());
    }

    public function testMoveWidget(): void
    {
        $dashboard = new Dashboard(Uuid::v4(), Uuid::v4());
        
        $widget = new Widget(
            id: Uuid::v4(),
            dashboard: $dashboard,
            type: WidgetType::PRODUCT_SEARCH,
            row: 1,
            column: 1,
            configuration: []
        );

        $dashboard->addWidget($widget);
        $dashboard->moveWidget($widget->getId(), 2, 2);

        $widgets = $dashboard->getWidgets();
        $this->assertEquals(2, $widgets[0]->getRow());
        $this->assertEquals(2, $widgets[0]->getColumn());
    }

    public function testCannotMoveWidgetToOccupiedPosition(): void
    {
        $this->expectException(WidgetPositionAlreadyOccupiedException::class);

        $dashboard = new Dashboard(Uuid::v4(), Uuid::v4());
        
        $widget1 = new Widget(
            id: Uuid::v4(),
            dashboard: $dashboard,
            type: WidgetType::PRODUCT_SEARCH,
            row: 1,
            column: 1,
            configuration: []
        );

        $widget2 = new Widget(
            id: Uuid::v4(),
            dashboard: $dashboard,
            type: WidgetType::NUTRISCORE_COMPARISON,
            row: 2,
            column: 2,
            configuration: []
        );

        $dashboard->addWidget($widget1);
        $dashboard->addWidget($widget2);

        $dashboard->moveWidget($widget1->getId(), 2, 2);
    }
}