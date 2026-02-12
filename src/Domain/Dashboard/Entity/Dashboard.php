<?php

declare(strict_types=1);

namespace Domain\Dashboard\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Domain\Dashboard\Exception\WidgetNotFoundException;
use Domain\Dashboard\Exception\WidgetPositionAlreadyOccupiedException;
use Domain\Dashboard\ValueObject\WidgetPosition;
use Domain\Dashboard\ValueObject\WidgetType;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'dashboards')]
class Dashboard
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    private Uuid $id;

    #[ORM\Column(name: 'user_id', type: 'uuid', unique: true)]
    private Uuid $userId;

    #[ORM\OneToMany(targetEntity: Widget::class, mappedBy: 'dashboard', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $widgets;

    #[ORM\Column(name: 'created_at', type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(name: 'updated_at', type: 'datetime_immutable')]
    private \DateTimeImmutable $updatedAt;

    private function __construct(
        Uuid $id,
        Uuid $userId,
    ) {
        $this->id = $id;
        $this->userId = $userId;
        $this->widgets = new ArrayCollection();
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
        return $this->widgets->toArray();
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
            dashboard: $this,
            type: $type,
            row: $row,
            column: $column,
            configuration: $configuration,
        );

        $this->widgets->add($widget);
        $this->updatedAt = new \DateTimeImmutable();

        return $widget;
    }

    public function removeWidget(Uuid $widgetId): void
    {
        $widget = $this->findWidget($widgetId);
        
        if (!$widget) {
            throw WidgetNotFoundException::withId($widgetId);
        }

        $this->widgets->removeElement($widget);
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function moveWidget(Uuid $widgetId, int $newRow, int $newColumn): void
    {
        $widget = $this->findWidget($widgetId);
        
        if (!$widget) {
            throw WidgetNotFoundException::withId($widgetId);
        }

        $newPosition = WidgetPosition::at($newRow, $newColumn);
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

    private function findWidget(Uuid $widgetId): ?Widget
    {
        foreach ($this->widgets as $widget) {
            if ($widget->getId()->equals($widgetId)) {
                return $widget;
            }
        }
        return null;
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