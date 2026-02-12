<?php

declare(strict_types=1);

namespace Infrastructure\Doctrine\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Domain\Identity\Entity\LoginAttempt;
use Domain\Identity\Repository\LoginAttemptRepositoryInterface;

final class LoginAttemptRepository extends ServiceEntityRepository implements LoginAttemptRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, LoginAttempt::class);
    }

    public function save(LoginAttempt $attempt): void
    {
        $this->getEntityManager()->persist($attempt);
        $this->getEntityManager()->flush();
    }
}