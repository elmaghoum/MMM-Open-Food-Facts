<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Dashboard;

use Domain\Dashboard\Entity\Dashboard;
use Domain\Dashboard\Entity\Widget;
use Domain\Dashboard\ValueObject\WidgetType;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

final class WidgetTest extends TestCase
{
    public function testWidgetCanBeCreated(): void
    {
        $dashboard = Dashboard::create(Uuid::v4());
        
        $widget = new Widget(
            id: Uuid::v4(),
            dashboard: $dashboard,
            type: WidgetType::PIE_CHART,
            row: 1,
            column: 1,
            configuration: ['data' => 'nutriscore']
        );

        $this->assertInstanceOf(Widget::class, $widget);
        $this->assertEquals(1, $widget->getRow());
        $this->assertEquals(1, $widget->getColumn());
        $this->assertEquals($dashboard->getId(), $widget->getDashboardId());
    }

    public function testWidgetCanBeMovedToNewPosition(): void
    {
        $dashboard = Dashboard::create(Uuid::v4());
        
        $widget = new Widget(
            id: Uuid::v4(),
            dashboard: $dashboard,
            type: WidgetType::PIE_CHART,
            row: 1,
            column: 1,
            configuration: []
        );

        $widget->moveTo(3, 2);

        $this->assertEquals(3, $widget->getRow());
        $this->assertEquals(2, $widget->getColumn());
    }

    public function testWidgetConfigurationCanBeUpdated(): void
    {
        $dashboard = Dashboard::create(Uuid::v4());
        
        $widget = new Widget(
            id: Uuid::v4(),
            dashboard: $dashboard,
            type: WidgetType::PIE_CHART,
            row: 1,
            column: 1,
            configuration: ['data' => 'nutriscore']
        );

        $newConfig = ['data' => 'nova', 'limit' => 10];
        $widget->updateConfiguration($newConfig);

        $this->assertEquals($newConfig, $widget->getConfiguration());
    }
}