<?php

declare(strict_types=1);

namespace Infrastructure\Doctrine\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Domain\Identity\Entity\User;
use Domain\Identity\Repository\UserRepositoryInterface;
use Symfony\Component\Uid\Uuid;

final class UserRepository extends ServiceEntityRepository implements UserRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    public function save(User $user): void
    {
        $this->getEntityManager()->persist($user);
        $this->getEntityManager()->flush();
    }

    public function findById(Uuid $id): ?User
    {
        return $this->find($id);
    }

    public function findByEmail(string $email): ?User
    {
        return $this->findOneBy(['email' => $email]);
    }

    public function findAll(): array
    {
        return parent::findAll(); 
    }

    public function countUsers(): int 
    {
        return parent::count([]); 
    }

    public function delete(User $user): void
    {
        $this->getEntityManager()->remove($user);
        $this->getEntityManager()->flush();
    }
}