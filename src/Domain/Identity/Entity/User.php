<?php

declare(strict_types=1);

namespace Domain\Identity\Entity;

use Domain\Identity\Exception\UserBlockedException;
use Symfony\Component\Uid\Uuid;

final class User
{
    private const MAX_FAILED_ATTEMPTS = 5;
    private const BLOCK_DURATION_MINUTES = 15;

    private int $failedLoginAttempts = 0;
    private ?\DateTimeImmutable $blockedUntil = null;
    private \DateTimeImmutable $createdAt;
    private \DateTimeImmutable $updatedAt;

    public function __construct(
        private readonly Uuid $id,
        private string $email,
        private string $passwordHash,
    ) {
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getPasswordHash(): string
    {
        return $this->passwordHash;
    }

    public function getFailedLoginAttempts(): int
    {
        return $this->failedLoginAttempts;
    }

    public function getBlockedUntil(): ?\DateTimeImmutable
    {
        return $this->blockedUntil;
    }

    public function recordFailedLogin(): void
    {
        $this->failedLoginAttempts++;
        $this->updatedAt = new \DateTimeImmutable();

        if ($this->failedLoginAttempts >= self::MAX_FAILED_ATTEMPTS) {
            $this->blockAccount();
        }
    }

    public function recordSuccessfulLogin(): void
    {
        $this->failedLoginAttempts = 0;
        $this->blockedUntil = null;
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function isBlocked(?\DateTimeImmutable $now = null): bool
    {
        if ($this->blockedUntil === null) {
            return false;
        }

        $now = $now ?? new \DateTimeImmutable();

        return $now < $this->blockedUntil;
    }

    public function ensureNotBlocked(): void
    {
        if ($this->isBlocked()) {
            throw UserBlockedException::create();
        }
    }

    private function blockAccount(): void
    {
        $this->blockedUntil = (new \DateTimeImmutable())
            ->modify(sprintf('+%d minutes', self::BLOCK_DURATION_MINUTES));
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }
}