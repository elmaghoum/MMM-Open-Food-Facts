<?php

declare(strict_types=1);

namespace Application\Dashboard\DTO;

use Symfony\Component\Uid\Uuid;

final readonly class AddWidgetResult
{
    private function __construct(
        private bool $success,
        private ?Uuid $widgetId = null,
        private ?string $errorMessage = null,
    ) {
    }

    public static function success(Uuid $widgetId): self
    {
        return new self(
            success: true,
            widgetId: $widgetId,
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

    public function getWidgetId(): ?Uuid
    {
        return $this->widgetId;
    }

    public function getErrorMessage(): ?string
    {
        return $this->errorMessage;
    }
}