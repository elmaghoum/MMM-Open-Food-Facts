<?php

declare(strict_types=1);

namespace Infrastructure\Security;

use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Uid\Uuid;

final readonly class TwoFactorSessionStorage
{
    private const SESSION_KEY = '2fa_user_id';

    public function __construct(
        private RequestStack $requestStack,
    ) {
    }

    public function store(Uuid $userId): void
    {
        $session = $this->requestStack->getSession();
        $session->set(self::SESSION_KEY, $userId->toRfc4122());
    }

    public function get(): ?Uuid
    {
        $session = $this->requestStack->getSession();
        $userIdString = $session->get(self::SESSION_KEY);

        if (!$userIdString) {
            return null;
        }

        return Uuid::fromString($userIdString);
    }

    public function clear(): void
    {
        $session = $this->requestStack->getSession();
        $session->remove(self::SESSION_KEY);
    }
}