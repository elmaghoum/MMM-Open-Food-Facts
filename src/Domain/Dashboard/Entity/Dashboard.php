<?php

declare(strict_types=1);

namespace Domain\Dashboard\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Domain\Dashboard\Exception\WidgetNotFoundException;
use Domain\Dashboard\Exception\WidgetPositionAlreadyOccupiedException;
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

    public function __construct(Uuid $id, Uuid $userId)
    {
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

    public function addWidget(Widget $widget): void
    {
        // Vérifier que la position n'est pas déjà occupée
        foreach ($this->widgets as $existingWidget) {
            if ($existingWidget->getRow() === $widget->getRow() && 
                $existingWidget->getColumn() === $widget->getColumn()) {
                throw new WidgetPositionAlreadyOccupiedException(
                    sprintf('Position (%d, %d) already occupied', $widget->getRow(), $widget->getColumn())
                );
            }
        }

        $this->widgets->add($widget);
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function removeWidget(Uuid $widgetId): void
    {
        foreach ($this->widgets as $widget) {
            if ($widget->getId()->equals($widgetId)) {
                $this->widgets->removeElement($widget);
                $this->updatedAt = new \DateTimeImmutable();
                return;
            }
        }

        throw new WidgetNotFoundException(
            sprintf('Widget with ID %s not found', $widgetId->toRfc4122())
        );
    }

    public function moveWidget(Uuid $widgetId, int $newRow, int $newColumn): void
    {
        $widget = $this->findWidget($widgetId);
        
        if (!$widget) {
            throw new WidgetNotFoundException(
                sprintf('Widget with ID %s not found', $widgetId->toRfc4122())
            );
        }

        // Vérifier que la nouvelle position n'est pas occupée (sauf par ce widget)
        foreach ($this->widgets as $existingWidget) {
            if ($existingWidget->getId()->equals($widgetId)) {
                continue; // Ignorer le widget qu'on déplace
            }

            if ($existingWidget->getRow() === $newRow && 
                $existingWidget->getColumn() === $newColumn) {
                throw new WidgetPositionAlreadyOccupiedException(
                    sprintf('Position (%d, %d) already occupied', $newRow, $newColumn)
                );
            }
        }

        $widget->moveTo($newRow, $newColumn);
        $this->updatedAt = new \DateTimeImmutable();
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