<?php

declare(strict_types=1);

namespace Domain\Identity\Repository;

use Domain\Identity\Entity\TwoFactorCode;
use Symfony\Component\Uid\Uuid;

interface TwoFactorCodeRepositoryInterface
{
    public function save(TwoFactorCode $code): void;
    public function findActiveByUserId(Uuid $userId): ?TwoFactorCode;
}