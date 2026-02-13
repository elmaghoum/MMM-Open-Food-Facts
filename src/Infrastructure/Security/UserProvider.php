<?php

declare(strict_types=1);

namespace Infrastructure\Security;

use Domain\Identity\Entity\User as DomainUser;
use Domain\Identity\Repository\UserRepositoryInterface;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

final readonly class UserProvider implements UserProviderInterface
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
    ) {
    }

    public function refreshUser(UserInterface $user): UserInterface
    {
        if (!$user instanceof UserAdapter) {
            throw new \InvalidArgumentException('Invalid user class');
        }

        $domainUser = $this->userRepository->findById($user->getId());
        
        if (!$domainUser) {
            throw new UserNotFoundException();
        }

        return UserAdapter::fromDomainUser($domainUser);
    }

    public function supportsClass(string $class): bool
    {
        return UserAdapter::class === $class || is_subclass_of($class, UserAdapter::class);
    }

    public function loadUserByIdentifier(string $identifier): UserInterface
    {
        $user = $this->userRepository->findByEmail($identifier);

        if (!$user) {
            throw new UserNotFoundException();
        }

        return UserAdapter::fromDomainUser($user);
    }
}