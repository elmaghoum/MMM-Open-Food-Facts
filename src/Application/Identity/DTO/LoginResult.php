<?php

declare(strict_types=1);

namespace Application\Identity\DTO;

use Domain\Identity\Entity\TwoFactorCode;
use Symfony\Component\Uid\Uuid;

final readonly class LoginResult
{
    private function __construct(
        private bool $success,
        private ?Uuid $userId = null,
        private ?TwoFactorCode $twoFactorCode = null,
        private ?string $errorMessage = null,
    ) {
    }

    public static function success(Uuid $userId, TwoFactorCode $twoFactorCode): self
    {
        return new self(
            success: true,
            userId: $userId,
            twoFactorCode: $twoFactorCode,
        );
    }

    public static function failure(string $errorMessage): self
    {
        return new self(
            success: false,
            errorMessage: $errorMessage,
        );
    }

    public function isSuccess(): bool
    {
        return $this->success;
    }

    public function getUserId(): ?Uuid
    {
        return $this->userId;
    }

    public function getTwoFactorCode(): ?TwoFactorCode
    {
        return $this->twoFactorCode;
    }

    public function getErrorMessage(): ?string
    {
        return $this->errorMessage;
    }
}