<?php

declare(strict_types=1);

namespace Domain\Dashboard\ValueObject;

use Domain\Dashboard\Exception\InvalidWidgetPositionException;

final readonly class WidgetPosition
{
    private function __construct(
        public int $row,
        public int $column,
    ) {
        if ($column < 1 || $column > 2) {
            throw InvalidWidgetPositionException::invalidColumn($column);
        }

        if ($row < 1) {
            throw InvalidWidgetPositionException::invalidRow($row);
        }
    }

    public static function at(int $row, int $column): self
    {
        return new self($row, $column);
    }

    public function equals(WidgetPosition $other): bool
    {
        return $this->row === $other->row && $this->column === $other->column;
    }
}