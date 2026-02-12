<?php

declare(strict_types=1);

namespace Domain\Dashboard\Entity;

use Doctrine\ORM\Mapping as ORM;
use Domain\Dashboard\ValueObject\WidgetPosition;
use Domain\Dashboard\ValueObject\WidgetType;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'widgets')]
#[ORM\UniqueConstraint(name: 'unique_dashboard_position', columns: ['dashboard_id', 'row', 'column'])]
class Widget
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    private Uuid $id;

    #[ORM\ManyToOne(targetEntity: Dashboard::class, inversedBy: 'widgets')]
    #[ORM\JoinColumn(name: 'dashboard_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private Dashboard $dashboard;

    #[ORM\Column(type: 'string', length: 50, enumType: WidgetType::class)]
    private WidgetType $type;

    #[ORM\Column(type: 'integer')]
    private int $row;

    #[ORM\Column(type: 'integer')]
    private int $column;

    #[ORM\Column(type: 'json')]
    private array $configuration;

    #[ORM\Column(name: 'created_at', type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(name: 'updated_at', type: 'datetime_immutable')]
    private \DateTimeImmutable $updatedAt;

    private WidgetPosition $position;

    public function __construct(
        Uuid $id,
        Dashboard $dashboard,
        WidgetType $type,
        int $row,
        int $column,
        array $configuration,
    ) {
        $this->id = $id;
        $this->dashboard = $dashboard;
        $this->type = $type;
        $this->row = $row;
        $this->column = $column;
        $this->configuration = $configuration;
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
        return $this->dashboard->getId();
    }

    public function getType(): WidgetType
    {
        return $this->type;
    }

    public function getRow(): int
    {
        return $this->row;
    }

    public function getColumn(): int
    {
        return $this->column;
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
        $this->row = $row;
        $this->column = $column;
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