<?php

declare(strict_types=1);

namespace Domain\Identity\Repository;

use Domain\Identity\Entity\User;
use Symfony\Component\Uid\Uuid;

interface UserRepositoryInterface
{
    public function save(User $user): void;
    public function findById(Uuid $id): ?User;
    public function findByEmail(string $email): ?User;
}