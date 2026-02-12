<?php

declare(strict_types=1);

namespace Infrastructure\Doctrine\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Domain\Identity\Entity\TwoFactorCode;
use Domain\Identity\Repository\TwoFactorCodeRepositoryInterface;
use Symfony\Component\Uid\Uuid;

final class TwoFactorCodeRepository extends ServiceEntityRepository implements TwoFactorCodeRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TwoFactorCode::class);
    }

    public function save(TwoFactorCode $code): void
    {
        $this->getEntityManager()->persist($code);
        $this->getEntityManager()->flush();
    }

    public function findActiveByUserId(Uuid $userId): ?TwoFactorCode
    {
        return $this->createQueryBuilder('tfc')
            ->where('tfc.userId = :userId')
            ->andWhere('tfc.usedAt IS NULL')
            ->andWhere('tfc.expiresAt > :now')
            ->setParameter('userId', $userId)
            ->setParameter('now', new \DateTimeImmutable())
            ->orderBy('tfc.createdAt', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
}