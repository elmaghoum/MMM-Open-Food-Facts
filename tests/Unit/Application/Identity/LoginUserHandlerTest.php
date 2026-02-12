<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Identity;

use Application\Identity\Command\LoginUserCommand;
use Application\Identity\Handler\LoginUserHandler;
use Domain\Identity\Entity\User;
use Domain\Identity\Entity\TwoFactorCode;
use Domain\Identity\Repository\UserRepositoryInterface;
use Domain\Identity\Repository\TwoFactorCodeRepositoryInterface;
use Domain\Identity\Repository\LoginAttemptRepositoryInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

final class LoginUserHandlerTest extends TestCase
{
    /** @var UserRepositoryInterface&MockObject */
    private $userRepository;

    /** @var TwoFactorCodeRepositoryInterface&MockObject */
    private $twoFactorCodeRepository;

    /** @var LoginAttemptRepositoryInterface&MockObject */
    private $loginAttemptRepository;

    private LoginUserHandler $handler;

    protected function setUp(): void
    {
        $this->userRepository = $this->createMock(UserRepositoryInterface::class);
        $this->twoFactorCodeRepository = $this->createMock(TwoFactorCodeRepositoryInterface::class);
        $this->loginAttemptRepository = $this->createMock(LoginAttemptRepositoryInterface::class);

        $this->handler = new LoginUserHandler(
            $this->userRepository,
            $this->twoFactorCodeRepository,
            $this->loginAttemptRepository
        );
    }

    public function testSuccessfulLoginGenerates2FACode(): void
    {
        $user = new User(
            Uuid::v4(),
            'test@example.com',
            password_hash('password123', PASSWORD_BCRYPT)
        );

        $this->userRepository
            ->expects($this->once())
            ->method('findByEmail')
            ->with('test@example.com')
            ->willReturn($user);

        $this->twoFactorCodeRepository
            ->expects($this->once())
            ->method('save')
            ->with($this->isInstanceOf(TwoFactorCode::class));

        $this->userRepository
            ->expects($this->once())
            ->method('save')
            ->with($user);

        $this->loginAttemptRepository
            ->expects($this->once())
            ->method('save');

        $command = new LoginUserCommand(
            'test@example.com',
            'password123',
            '127.0.0.1'
        );

        $result = $this->handler->handle($command);

        $this->assertTrue($result->isSuccess());
        $this->assertNotNull($result->getTwoFactorCode());
    }

    public function testFailedLoginIncrementsAttempts(): void
    {
        $user = new User(
            Uuid::v4(),
            'test@example.com',
            password_hash('password123', PASSWORD_BCRYPT)
        );

        $this->userRepository
            ->expects($this->once())
            ->method('findByEmail')
            ->willReturn($user);

        $this->userRepository
            ->expects($this->once())
            ->method('save')
            ->with($user);

        $this->twoFactorCodeRepository
            ->expects($this->never())
            ->method('save');

        $this->loginAttemptRepository
            ->expects($this->once())
            ->method('save');

        $command = new LoginUserCommand(
            'test@example.com',
            'wrong_password',
            '127.0.0.1'
        );

        $result = $this->handler->handle($command);

        $this->assertFalse($result->isSuccess());
        $this->assertSame(1, $user->getFailedLoginAttempts());
    }

    public function testBlockedUserCannotLogin(): void
    {
        $user = new User(
            Uuid::v4(),
            'test@example.com',
            password_hash('password123', PASSWORD_BCRYPT)
        );

        // Simule un blocage (ex: 5 tentatives échouées)
        for ($i = 0; $i < 5; $i++) {
            $user->recordFailedLogin();
        }

        $this->userRepository
            ->expects($this->once())
            ->method('findByEmail')
            ->willReturn($user);

        $this->userRepository
            ->expects($this->never())
            ->method('save');

        $this->twoFactorCodeRepository
            ->expects($this->never())
            ->method('save');

        $this->loginAttemptRepository
            ->expects($this->once())
            ->method('save');

        $command = new LoginUserCommand(
            'test@example.com',
            'password123',
            '127.0.0.1'
        );

        $result = $this->handler->handle($command);

        $this->assertFalse($result->isSuccess());
        $this->assertSame('Account is temporarily blocked', $result->getErrorMessage());
    }

    public function testNonExistentUserRecordsFailure(): void
    {
        $this->userRepository
            ->expects($this->once())
            ->method('findByEmail')
            ->with('nonexistent@example.com')
            ->willReturn(null);

        $this->userRepository
            ->expects($this->never())
            ->method('save');

        $this->twoFactorCodeRepository
            ->expects($this->never())
            ->method('save');

        $this->loginAttemptRepository
            ->expects($this->once())
            ->method('save');

        $command = new LoginUserCommand(
            'nonexistent@example.com',
            'password',
            '127.0.0.1'
        );

        $result = $this->handler->handle($command);

        $this->assertFalse($result->isSuccess());
    }
}
