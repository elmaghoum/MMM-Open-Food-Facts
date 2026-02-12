<?php

declare(strict_types=1);

namespace Domain\Identity\Repository;

use Domain\Identity\Entity\LoginAttempt;

interface LoginAttemptRepositoryInterface
{
    public function save(LoginAttempt $attempt): void;
}