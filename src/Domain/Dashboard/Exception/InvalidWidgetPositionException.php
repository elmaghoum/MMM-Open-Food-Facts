<?php

declare(strict_types=1);

namespace Domain\Dashboard\Exception;

final class InvalidWidgetPositionException extends \DomainException
{
    public static function invalidColumn(int $column): self
    {
        return new self('Column must be 1 or 2');
    }

    public static function invalidRow(int $row): self
    {
        return new self('Row must be at least 1');
    }
}