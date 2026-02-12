<?php

declare(strict_types=1);

namespace Domain\Dashboard\ValueObject;

enum WidgetType: string
{
    case PIE_CHART = 'pie_chart';
    case DOUGHNUT_CHART = 'doughnut_chart';
    case BAR_CHART = 'bar_chart';
    case LINE_CHART = 'line_chart';
    case RADAR_CHART = 'radar_chart';
    case POLAR_AREA_CHART = 'polar_area_chart';
    case MIXED_CHART = 'mixed_chart';
}