<?php

declare(strict_types=1);

namespace Infrastructure\Doctrine\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Domain\Dashboard\Entity\Dashboard;
use Domain\Dashboard\Repository\DashboardRepositoryInterface;
use Symfony\Component\Uid\Uuid;

final class DashboardRepository extends ServiceEntityRepository implements DashboardRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Dashboard::class);
    }

    public function save(Dashboard $dashboard): void
    {
        $this->getEntityManager()->persist($dashboard);
        $this->getEntityManager()->flush();
    }

    public function findByUserId(Uuid $userId): ?Dashboard
    {
        return $this->findOneBy(['userId' => $userId]);
    }

    public function findById(Uuid $id): ?Dashboard
    {
        return $this->find($id);
    }
}