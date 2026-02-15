<?php

declare(strict_types=1);

namespace Domain\Dashboard\ValueObject;

enum WidgetType: string
{
    case PRODUCT_SEARCH = 'product_search';
    case SUGAR_SALT_COMPARISON = 'sugar_salt_comparison';
    case NUTRISCORE_COMPARISON = 'nutriscore_comparison';
    case NOVA_COMPARISON = 'nova_comparison';
    case NUTRITION_PIE = 'nutrition_pie';
}