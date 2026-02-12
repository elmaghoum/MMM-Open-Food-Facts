<?php

declare(strict_types=1);

namespace Domain\Dashboard\Exception;

use Symfony\Component\Uid\Uuid;

final class WidgetNotFoundException extends \DomainException
{
    public static function withId(Uuid $id): self
    {
        return new self(sprintf('Widget with id %s not found', $id->toRfc4122()));
    }
}