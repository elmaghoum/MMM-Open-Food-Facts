<?php

declare(strict_types=1);

namespace Domain\Dashboard\Entity;

use Domain\Dashboard\ValueObject\WidgetPosition;
use Domain\Dashboard\ValueObject\WidgetType;
use Symfony\Component\Uid\Uuid;

final class Widget
{
    private WidgetPosition $position;
    private \DateTimeImmutable $createdAt;
    private \DateTimeImmutable $updatedAt;

    public function __construct(
        private readonly Uuid $id,
        private readonly Uuid $dashboardId,
        private readonly WidgetType $type,
        int $row,
        int $column,
        private array $configuration,
    ) {
        $this->position = WidgetPosition::at($row, $column);
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getDashboardId(): Uuid
    {
        return $this->dashboardId;
    }

    public function getType(): WidgetType
    {
        return $this->type;
    }

    public function getRow(): int
    {
        return $this->position->row;
    }

    public function getColumn(): int
    {
        return $this->position->column;
    }

    public function getPosition(): WidgetPosition
    {
        return $this->position;
    }

    public function getConfiguration(): array
    {
        return $this->configuration;
    }

    public function moveTo(int $row, int $column): void
    {
        $this->position = WidgetPosition::at($row, $column);
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function updateConfiguration(array $configuration): void
    {
        $this->configuration = $configuration;
        $this->updatedAt = new \DateTimeImmutable();
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