<?php

declare(strict_types=1);

namespace Application\Dashboard\Command;

use Symfony\Component\Uid\Uuid;

final readonly class MoveWidgetCommand
{
    public function __construct(
        public Uuid $dashboardId,
        public Uuid $widgetId,
        public int $newRow,
        public int $newColumn,
    ) {
    }
}