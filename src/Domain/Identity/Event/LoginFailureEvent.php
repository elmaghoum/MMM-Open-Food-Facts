<?php

declare(strict_types=1);

namespace Domain\Identity\Event;

final readonly class LoginFailureEvent
{
    public function __construct(
        public string $email,
        public string $ipAddress,
        public string $reason,
    ) {
    }
}