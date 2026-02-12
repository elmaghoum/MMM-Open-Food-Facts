<?php

declare(strict_types=1);

namespace Domain\Dashboard\Repository;

use Domain\Dashboard\Entity\Dashboard;
use Symfony\Component\Uid\Uuid;

interface DashboardRepositoryInterface
{
    public function save(Dashboard $dashboard): void;
    public function findByUserId(Uuid $userId): ?Dashboard;
    public function findById(Uuid $id): ?Dashboard;
}