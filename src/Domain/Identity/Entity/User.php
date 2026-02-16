<?php

declare(strict_types=1);

namespace Domain\Identity\Entity;

use Doctrine\ORM\Mapping as ORM;
use Domain\Identity\Exception\UserBlockedException;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'users')]
class User
{
    private const MAX_FAILED_ATTEMPTS = 5;
    private const BLOCK_DURATION_MINUTES = 15;

    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    private Uuid $id;

    #[ORM\Column(type: 'string', length: 180, unique: true)]
    private string $email;

    #[ORM\Column(name: 'password_hash', type: 'string', length: 255)]
    private string $passwordHash;

    #[ORM\Column(type: 'json')]
    private array $roles = ['ROLE_USER'];

    #[ORM\Column(name: 'is_active', type: 'boolean')]
    private bool $isActive = true;

    #[ORM\Column(name: 'failed_login_attempts', type: 'integer')]
    private int $failedLoginAttempts = 0;

    #[ORM\Column(name: 'blocked_until', type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $blockedUntil = null;

    #[ORM\Column(name: 'created_at', type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(name: 'updated_at', type: 'datetime_immutable')]
    private \DateTimeImmutable $updatedAt;

    public function __construct(
        Uuid $id,
        string $email,
        string $passwordHash,
        array $roles = ['ROLE_USER'],
    ) {
        $this->id = $id;
        $this->email = $email;
        $this->passwordHash = $passwordHash;
        $this->roles = array_unique(array_merge(['ROLE_USER'], $roles));
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

    public function getRoles(): array
    {
        return $this->roles;
    }

    public function hasRole(string $role): bool
    {
        return in_array($role, $this->roles);
    }

    public function isAdmin(): bool
    {
        return $this->hasRole('ROLE_ADMIN');
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function activate(): void
    {
        $this->isActive = true;
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function deactivate(): void
    {
        $this->isActive = false;
        $this->updatedAt = new \DateTimeImmutable();
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