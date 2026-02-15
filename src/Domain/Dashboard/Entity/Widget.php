<?php

declare(strict_types=1);

namespace Domain\Dashboard\Entity;

use Domain\Dashboard\ValueObject\WidgetPosition;
use Domain\Dashboard\ValueObject\WidgetType;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'widgets')]
#[ORM\UniqueConstraint(name: 'unique_position', columns: ['dashboard_id', 'row', 'widget_column'])]
class Widget
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    private Uuid $id;

    #[ORM\Column(type: 'string', enumType: WidgetType::class)]
    private WidgetType $type;

    #[ORM\Column(type: 'integer')]
    private int $row;

    #[ORM\Column(name: 'widget_column', type: 'integer')]
    private int $column;

    #[ORM\Column(type: 'json')]
    private array $configuration;

    #[ORM\ManyToOne(targetEntity: Dashboard::class, inversedBy: 'widgets')]
    #[ORM\JoinColumn(nullable: false)]
    private Dashboard $dashboard;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    public function __construct(
        Uuid $id,
        Dashboard $dashboard,
        WidgetType $type,
        int $row,
        int $column,
        array $configuration = []
    ) {
        $position = WidgetPosition::at($row, $column);
        
        $this->id = $id;
        $this->dashboard = $dashboard;
        $this->type = $type;
        $this->row = $position->row; 
        $this->column = $position->column;  
        $this->configuration = $configuration;
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): Uuid
    {
        return $this->id;
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

    public function getConfiguration(): array
    {
        return $this->configuration;
    }

    public function getDashboard(): Dashboard
    {
        return $this->dashboard;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function moveTo(int $newRow, int $newColumn): void
    {
        $position = WidgetPosition::at($newRow, $newColumn);
        $this->row = $position->row;  
        $this->column = $position->column;  
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function updateConfiguration(array $configuration): void
    {
        $this->configuration = $configuration;
        $this->updatedAt = new \DateTimeImmutable();
    }
}