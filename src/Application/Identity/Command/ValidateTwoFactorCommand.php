<?php

declare(strict_types=1);

namespace Application\Identity\Command;

use Symfony\Component\Uid\Uuid;

final readonly class ValidateTwoFactorCommand
{
    public function __construct(
        public Uuid $userId,
        public string $code,
    ) {
    }
}