<?php

declare(strict_types=1);

namespace Domain\Identity\Exception;

final class TwoFactorCodeExpiredException extends \DomainException
{
    public static function create(): self
    {
        return new self('Two-factor authentication code has expired');
    }
}