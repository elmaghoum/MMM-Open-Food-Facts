<?php

declare(strict_types=1);

namespace Application\Identity\DTO;

final readonly class TwoFactorValidationResult
{
    private function __construct(
        private bool $valid,
        private ?string $errorMessage = null,
    ) {
    }

    public static function valid(): self
    {
        return new self(true);
    }

    public static function invalid(string $errorMessage): self
    {
        return new self(false, $errorMessage);
    }

    public function isValid(): bool
    {
        return $this->valid;
    }

    public function getErrorMessage(): ?string
    {
        return $this->errorMessage;
    }
}