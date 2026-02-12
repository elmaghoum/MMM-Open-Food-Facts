<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Identity;

use Domain\Identity\Entity\User;
use Domain\Identity\Exception\UserBlockedException;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

final class UserTest extends TestCase
{
    public function testUserCanBeCreated(): void
    {
        $user = new User(
            id: Uuid::v4(),
            email: 'test@example.com',
            passwordHash: 'hashed_password'
        );

        $this->assertInstanceOf(User::class, $user);
        $this->assertEquals('test@example.com', $user->getEmail());
    }

    public function testFailedLoginIncrementsAttempts(): void
    {
        $user = new User(Uuid::v4(), 'test@example.com', 'hash');

        $this->assertEquals(0, $user->getFailedLoginAttempts());

        $user->recordFailedLogin();
        $this->assertEquals(1, $user->getFailedLoginAttempts());

        $user->recordFailedLogin();
        $this->assertEquals(2, $user->getFailedLoginAttempts());
    }

    public function testUserIsBlockedAfter5FailedAttempts(): void
    {
        $user = new User(Uuid::v4(), 'test@example.com', 'hash');

        for ($i = 0; $i < 5; $i++) {
            $user->recordFailedLogin();
        }

        $this->assertTrue($user->isBlocked());
        $this->assertNotNull($user->getBlockedUntil());
    }

    public function testBlockedUserThrowsExceptionOnLogin(): void
    {
        $this->expectException(UserBlockedException::class);
        $this->expectExceptionMessage('User account is temporarily blocked');

        $user = new User(Uuid::v4(), 'test@example.com', 'hash');

        // Bloquer le compte
        for ($i = 0; $i < 5; $i++) {
            $user->recordFailedLogin();
        }

        // Tenter de vérifier si bloqué devrait lever une exception
        $user->ensureNotBlocked();
    }

    public function testSuccessfulLoginResetsAttempts(): void
    {
        $user = new User(Uuid::v4(), 'test@example.com', 'hash');

        $user->recordFailedLogin();
        $user->recordFailedLogin();
        $this->assertEquals(2, $user->getFailedLoginAttempts());

        $user->recordSuccessfulLogin();
        $this->assertEquals(0, $user->getFailedLoginAttempts());
    }

    public function testBlockExpiresAfter15Minutes(): void
    {
        $user = new User(Uuid::v4(), 'test@example.com', 'hash');

        // Bloquer
        for ($i = 0; $i < 5; $i++) {
            $user->recordFailedLogin();
        }

        $this->assertTrue($user->isBlocked());

        // Simuler 15 minutes + 1 seconde plus tard
        $futureTime = (new \DateTimeImmutable())->modify('+15 minutes +1 second');
        $this->assertFalse($user->isBlocked($futureTime));
    }
}