<?php

declare(strict_types=1);

namespace Domain\Identity\Exception;

final class UserBlockedException extends \DomainException
{
    public static function create(): self
    {
        return new self('User account is temporarily blocked');
    }
}