<?php

declare(strict_types=1);

namespace Infrastructure\Security;

use Domain\Identity\Entity\User as DomainUser;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Uid\Uuid;

final readonly class UserAdapter implements UserInterface, PasswordAuthenticatedUserInterface
{
    private function __construct(
        private Uuid $id,
        private string $email,
        private string $passwordHash,
        private array $roles,
        private bool $isActive,
    ) {
    }

    public static function fromDomainUser(DomainUser $user): self
    {
        return new self(
            id: $user->getId(),
            email: $user->getEmail(),
            passwordHash: $user->getPasswordHash(),
            roles: $user->getRoles(),
            isActive: $user->isActive(),
        );
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getUserIdentifier(): string
    {
        return $this->email;
    }

    public function getRoles(): array
    {
        return $this->roles;
    }

    public function getPassword(): string
    {
        return $this->passwordHash;
    }

    public function eraseCredentials(): void
    {
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function isAdmin(): bool
    {
        return in_array('ROLE_ADMIN', $this->roles);
    }
}