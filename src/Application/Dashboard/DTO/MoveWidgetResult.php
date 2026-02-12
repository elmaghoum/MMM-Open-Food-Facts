<?php

declare(strict_types=1);

namespace Application\Dashboard\DTO;

final readonly class MoveWidgetResult
{
    private function __construct(
        private bool $success,
        private ?string $errorMessage = null,
    ) {
    }

    public static function success(): self
    {
        return new self(success: true);
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

    public function getErrorMessage(): ?string
    {
        return $this->errorMessage;
    }
}