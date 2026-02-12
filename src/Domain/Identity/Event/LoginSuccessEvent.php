<?php

declare(strict_types=1);

namespace Domain\Identity\Event;

use Symfony\Component\Uid\Uuid;

final readonly class LoginSuccessEvent
{
    public function __construct(
        public Uuid $userId,
        public string $email,
        public string $ipAddress,
    ) {
    }
}