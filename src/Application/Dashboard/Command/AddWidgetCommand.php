<?php

declare(strict_types=1);

namespace Application\Dashboard\Command;

use Domain\Dashboard\ValueObject\WidgetType;
use Symfony\Component\Uid\Uuid;

final readonly class AddWidgetCommand
{
    public function __construct(
        public Uuid $userId,
        public WidgetType $type,
        public int $row,
        public int $column,
        public array $configuration,
    ) {
    }
}