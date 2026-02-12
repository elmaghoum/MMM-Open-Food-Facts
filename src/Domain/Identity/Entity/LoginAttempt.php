<?php

declare(strict_types=1);

namespace Domain\Identity\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'login_attempts')]
class LoginAttempt
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    private Uuid $id;

    #[ORM\Column(type: 'string', length: 180)]
    private string $email;

    #[ORM\Column(type: 'boolean')]
    private bool $success;

    #[ORM\Column(name: 'ip_address', type: 'string', length: 45)]
    private string $ipAddress;

    #[ORM\Column(name: 'attempted_at', type: 'datetime_immutable')]
    private \DateTimeImmutable $attemptedAt;

    private function __construct(
        Uuid $id,
        string $email,
        bool $success,
        string $ipAddress,
    ) {
        $this->id = $id;
        $this->email = $email;
        $this->success = $success;
        $this->ipAddress = $ipAddress;
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