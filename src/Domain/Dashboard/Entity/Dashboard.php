<?php

declare(strict_types=1);

namespace Domain\Dashboard\Entity;

use Domain\Dashboard\Exception\WidgetNotFoundException;
use Domain\Dashboard\Exception\WidgetPositionAlreadyOccupiedException;
use Domain\Dashboard\ValueObject\WidgetPosition;
use Domain\Dashboard\ValueObject\WidgetType;
use Symfony\Component\Uid\Uuid;

final class Dashboard
{
    /** @var Widget[] */
    private array $widgets = [];
    private \DateTimeImmutable $createdAt;
    private \DateTimeImmutable $updatedAt;

    private function __construct(
        private readonly Uuid $id,
        private readonly Uuid $userId,
    ) {
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }

    public static function create(Uuid $userId): self
    {
        return new self(
            id: Uuid::v4(),
            userId: $userId,
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

    /**
     * @return Widget[]
     */
    public function getWidgets(): array
    {
        return array_values($this->widgets);
    }

    public function addWidget(
        WidgetType $type,
        int $row,
        int $column,
        array $configuration
    ): Widget {
        $position = WidgetPosition::at($row, $column);
        $this->ensurePositionIsAvailable($position);

        $widget = new Widget(
            id: Uuid::v4(),
            dashboardId: $this->id,
            type: $type,
            row: $row,
            column: $column,
            configuration: $configuration,
        );

        $this->widgets[$widget->getId()->toRfc4122()] = $widget;
        $this->updatedAt = new \DateTimeImmutable();

        return $widget;
    }

    public function removeWidget(Uuid $widgetId): void
    {
        $key = $widgetId->toRfc4122();
        
        if (!isset($this->widgets[$key])) {
            throw WidgetNotFoundException::withId($widgetId);
        }

        unset($this->widgets[$key]);
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function moveWidget(Uuid $widgetId, int $newRow, int $newColumn): void
    {
        $key = $widgetId->toRfc4122();
        
        if (!isset($this->widgets[$key])) {
            throw WidgetNotFoundException::withId($widgetId);
        }

        $widget = $this->widgets[$key];
        $newPosition = WidgetPosition::at($newRow, $newColumn);

        // Vérifier que la nouvelle position n'est pas occupée (sauf par le widget lui-même)
        $this->ensurePositionIsAvailable($newPosition, $widgetId);

        $widget->moveTo($newRow, $newColumn);
        $this->updatedAt = new \DateTimeImmutable();
    }

    private function ensurePositionIsAvailable(WidgetPosition $position, ?Uuid $excludeWidgetId = null): void
    {
        foreach ($this->widgets as $widget) {
            if ($excludeWidgetId && $widget->getId()->equals($excludeWidgetId)) {
                continue;
            }

            if ($widget->getPosition()->equals($position)) {
                throw WidgetPositionAlreadyOccupiedException::at($position->row, $position->column);
            }
        }
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