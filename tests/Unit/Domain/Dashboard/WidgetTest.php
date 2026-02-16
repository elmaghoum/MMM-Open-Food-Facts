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
        $dashboard = new Dashboard(Uuid::v4(), Uuid::v4());
        
        $widget = new Widget(
            id: Uuid::v4(),
            dashboard: $dashboard,
            type: WidgetType::QUICK_BARCODE_SEARCH, 
            row: 1,
            column: 1,
            configuration: ['barcode' => '123456']
        );

        $this->assertInstanceOf(Widget::class, $widget);
        $this->assertEquals(WidgetType::QUICK_BARCODE_SEARCH, $widget->getType());
        $this->assertEquals(1, $widget->getRow());
        $this->assertEquals(1, $widget->getColumn());
        $this->assertEquals(['barcode' => '123456'], $widget->getConfiguration());
    }

    public function testWidgetCanBeMovedToNewPosition(): void
    {
        $dashboard = new Dashboard(Uuid::v4(), Uuid::v4());
        
        $widget = new Widget(
            id: Uuid::v4(),
            dashboard: $dashboard,
            type: WidgetType::NUTRITION_PIE, 
            row: 1,
            column: 1,
            configuration: []
        );

        $widget->moveTo(2, 2);

        $this->assertEquals(2, $widget->getRow());
        $this->assertEquals(2, $widget->getColumn());
    }

    public function testWidgetConfigurationCanBeUpdated(): void
    {
        $dashboard = new Dashboard(Uuid::v4(), Uuid::v4());
        
        $widget = new Widget(
            id: Uuid::v4(),
            dashboard: $dashboard,
            type: WidgetType::SHOPPING_LIST, 
            row: 1,
            column: 1,
            configuration: ['barcodes' => []]
        );

        $newConfig = ['barcodes' => ['123456', '789012']];
        $widget->updateConfiguration($newConfig);

        $this->assertEquals($newConfig, $widget->getConfiguration());
    }
}