<?php

declare(strict_types=1);

namespace Domain\Identity\Entity;

use Symfony\Component\Uid\Uuid;

final class LoginAttempt
{
    private \DateTimeImmutable $attemptedAt;

    private function __construct(
        private readonly Uuid $id,
        private readonly string $email,
        private readonly bool $success,
        private readonly string $ipAddress,
    ) {
        $this->attemptedAt = new \DateTimeImmutable();
    }

    public static function recordSuccess(string $email, string $ipAddress): self
    {
        return new self(
            id: Uuid::v4(),
            email: $email,
            success: true,
            ipAddress: $ipAddress,
        );
    }

    public static function recordFailure(string $email, string $ipAddress): self
    {
        return new self(
            id: Uuid::v4(),
            email: $email,
            success: false,
            ipAddress: $ipAddress,
        );
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function isSuccess(): bool
    {
        return $this->success;
    }

    public function getIpAddress(): string
    {
        return $this->ipAddress;
    }

    public function getAttemptedAt(): \DateTimeImmutable
    {
        return $this->attemptedAt;
    }
}