<?php

declare(strict_types=1);

namespace Domain\Identity\Entity;

use Domain\Identity\Exception\TwoFactorCodeAlreadyUsedException;
use Domain\Identity\Exception\TwoFactorCodeExpiredException;
use Symfony\Component\Uid\Uuid;

final class TwoFactorCode
{
    private const EXPIRATION_MINUTES = 10;
    private const CODE_LENGTH = 6;

    private \DateTimeImmutable $createdAt;

    private function __construct(
        private readonly Uuid $id,
        private readonly Uuid $userId,
        private readonly string $code,
        private readonly \DateTimeImmutable $expiresAt,
        private ?\DateTimeImmutable $usedAt = null,
    ) {
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