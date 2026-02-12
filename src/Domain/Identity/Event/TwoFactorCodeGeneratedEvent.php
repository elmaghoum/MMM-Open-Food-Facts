<?php

declare(strict_types=1);

namespace Domain\Identity\Event;

use Domain\Identity\Entity\TwoFactorCode;

final readonly class TwoFactorCodeGeneratedEvent
{
    public function __construct(
        public string $email,
        public TwoFactorCode $twoFactorCode,
    ) {
    }
}