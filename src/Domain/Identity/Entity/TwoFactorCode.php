<?php

declare(strict_types=1);

namespace Domain\Identity\Entity;

use Doctrine\ORM\Mapping as ORM;
use Domain\Identity\Exception\TwoFactorCodeAlreadyUsedException;
use Domain\Identity\Exception\TwoFactorCodeExpiredException;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'two_factor_codes')]
class TwoFactorCode
{
    private const EXPIRATION_MINUTES = 10;
    private const CODE_LENGTH = 6;

    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    private Uuid $id;

    #[ORM\Column(name: 'user_id', type: 'uuid')]
    private Uuid $userId;

    #[ORM\Column(type: 'string', length: 10)]
    private string $code;

    #[ORM\Column(name: 'expires_at', type: 'datetime_immutable')]
    private \DateTimeImmutable $expiresAt;

    #[ORM\Column(name: 'used_at', type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $usedAt;

    #[ORM\Column(name: 'created_at', type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    private function __construct(
        Uuid $id,
        Uuid $userId,
        string $code,
        \DateTimeImmutable $expiresAt,
        ?\DateTimeImmutable $usedAt = null,
    ) {
        $this->id = $id;
        $this->userId = $userId;
        $this->code = $code;
        $this->expiresAt = $expiresAt;
        $this->usedAt = $usedAt;
        $this->createdAt = new \DateTimeImmutable();
    }

    public static function generate(Uuid $userId): self
    {
        $now = new \DateTimeImmutable();
        
        return new self(
            id: Uuid::v4(),
            userId: $userId,
            code: self::generateCode(),
            expiresAt: $now->modify(sprintf('+%d minutes', self::EXPIRATION_MINUTES)),
            usedAt: null,
        );
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getUserId(): Uuid
    {
        return $this->userId;
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function getExpiresAt(): \DateTimeImmutable
    {
        return $this->expiresAt;
    }

    public function getUsedAt(): ?\DateTimeImmutable
    {
        return $this->usedAt;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function isExpired(?\DateTimeImmutable $now = null): bool
    {
        $now = $now ?? new \DateTimeImmutable();
        return $now > $this->expiresAt;
    }

    public function isUsed(): bool
    {
        return $this->usedAt !== null;
    }

    public function markAsUsed(): void
    {
        $this->usedAt = new \DateTimeImmutable();
    }

    public function validate(string $inputCode, ?\DateTimeImmutable $now = null): bool
    {
        if ($this->isExpired($now)) {
            throw TwoFactorCodeExpiredException::create();
        }

        if ($this->isUsed()) {
            throw TwoFactorCodeAlreadyUsedException::create();
        }

        if ($this->code !== $inputCode) {
            return false;
        }

        $this->markAsUsed();
        return true;
    }

    private static function generateCode(): string
    {
        $code = '';
        for ($i = 0; $i < self::CODE_LENGTH; $i++) {
            $code .= random_int(0, 9);
        }
        return $code;
    }
}