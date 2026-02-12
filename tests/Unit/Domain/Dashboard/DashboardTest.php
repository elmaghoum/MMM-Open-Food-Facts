<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Dashboard;

use Domain\Dashboard\Entity\Dashboard;
use Domain\Dashboard\Entity\Widget;
use Domain\Dashboard\Exception\WidgetPositionAlreadyOccupiedException;
use Domain\Dashboard\Exception\InvalidWidgetPositionException;
use Domain\Dashboard\ValueObject\WidgetType;
use Domain\Dashboard\ValueObject\WidgetPosition;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

final class DashboardTest extends TestCase
{
    public function testDashboardCanBeCreated(): void
    {
        $userId = Uuid::v4();
        $dashboard = Dashboard::create($userId);

        $this->assertInstanceOf(Dashboard::class, $dashboard);
        $this->assertEquals($userId, $dashboard->getUserId());
        $this->assertCount(0, $dashboard->getWidgets());
    }

    public function testWidgetCanBeAddedToDashboard(): void
    {
        $dashboard = Dashboard::create(Uuid::v4());

        $widget = $dashboard->addWidget(
            type: WidgetType::PIE_CHART,
            row: 1,
            column: 1,
            configuration: ['data' => 'nutriscore']
        );

        $this->assertInstanceOf(Widget::class, $widget);
        $this->assertCount(1, $dashboard->getWidgets());
    }

    public function testCannotAddTwoWidgetsAtSamePosition(): void
    {
        $this->expectException(WidgetPositionAlreadyOccupiedException::class);

        $dashboard = Dashboard::create(Uuid::v4());

        $dashboard->addWidget(WidgetType::PIE_CHART, 1, 1, []);
        $dashboard->addWidget(WidgetType::BAR_CHART, 1, 1, []);
    }

    public function testColumnMustBe1Or2(): void
    {
        $this->expectException(InvalidWidgetPositionException::class);
        $this->expectExceptionMessage('Column must be 1 or 2');

        $dashboard = Dashboard::create(Uuid::v4());
        $dashboard->addWidget(WidgetType::PIE_CHART, 1, 3, []);
    }

    public function testRowMustBeAtLeast1(): void
    {
        $this->expectException(InvalidWidgetPositionException::class);
        $this->expectExceptionMessage('Row must be at least 1');

        $dashboard = Dashboard::create(Uuid::v4());
        $dashboard->addWidget(WidgetType::PIE_CHART, 0, 1, []);
    }

    public function testWidgetCanBeRemoved(): void
    {
        $dashboard = Dashboard::create(Uuid::v4());

        $widget = $dashboard->addWidget(WidgetType::PIE_CHART, 1, 1, []);
        $this->assertCount(1, $dashboard->getWidgets());

        $dashboard->removeWidget($widget->getId());
        $this->assertCount(0, $dashboard->getWidgets());
    }

    public function testWidgetCanBeMoved(): void
    {
        $dashboard = Dashboard::create(Uuid::v4());

        $widget = $dashboard->addWidget(WidgetType::PIE_CHART, 1, 1, []);
        
        $dashboard->moveWidget($widget->getId(), 2, 2);

        $movedWidget = $dashboard->getWidgets()[0];
        $this->assertEquals(2, $movedWidget->getRow());
        $this->assertEquals(2, $movedWidget->getColumn());
    }

    public function testCannotMoveWidgetToOccupiedPosition(): void
    {
        $this->expectException(WidgetPositionAlreadyOccupiedException::class);

        $dashboard = Dashboard::create(Uuid::v4());

        $widget1 = $dashboard->addWidget(WidgetType::PIE_CHART, 1, 1, []);
        $dashboard->addWidget(WidgetType::BAR_CHART, 2, 2, []);

        // Essayer de déplacer widget1 vers la position de widget2
        $dashboard->moveWidget($widget1->getId(), 2, 2);
    }

    public function testMultipleWidgetsInDifferentPositions(): void
    {
        $dashboard = Dashboard::create(Uuid::v4());

        $dashboard->addWidget(WidgetType::PIE_CHART, 1, 1, []);
        $dashboard->addWidget(WidgetType::BAR_CHART, 1, 2, []);
        $dashboard->addWidget(WidgetType::LINE_CHART, 2, 1, []);
        $dashboard->addWidget(WidgetType::RADAR_CHART, 2, 2, []);

        $this->assertCount(4, $dashboard->getWidgets());
    }
}