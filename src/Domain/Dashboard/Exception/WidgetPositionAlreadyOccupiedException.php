<?php

declare(strict_types=1);

namespace Domain\Dashboard\Exception;

final class WidgetPositionAlreadyOccupiedException extends \DomainException
{
    public static function at(int $row, int $column): self
    {
        return new self(sprintf('Position (row: %d, column: %d) is already occupied', $row, $column));
    }
}