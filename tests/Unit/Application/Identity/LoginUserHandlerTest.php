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
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Uid\Uuid;

final class LoginUserHandlerTest extends TestCase
{
    private UserRepositoryInterface $userRepository;
    private TwoFactorCodeRepositoryInterface $twoFactorCodeRepository;
    private LoginAttemptRepositoryInterface $loginAttemptRepository;
    private EventDispatcherInterface $eventDispatcher;
    private LoginUserHandler $handler;

    protected function setUp(): void
    {
        $this->userRepository = $this->createMock(UserRepositoryInterface::class);
        $this->twoFactorCodeRepository = $this->createMock(TwoFactorCodeRepositoryInterface::class);
        $this->loginAttemptRepository = $this->createMock(LoginAttemptRepositoryInterface::class);
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);

        $this->handler = new LoginUserHandler(
            $this->userRepository,
            $this->twoFactorCodeRepository,
            $this->loginAttemptRepository,
            $this->eventDispatcher
        );
    }

    public function testSuccessfulLoginGenerates2FACode(): void
    {
        $user = new User(Uuid::v4(), 'test@example.com', password_hash('password123', PASSWORD_BCRYPT));
        
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

        // Vérifier que les events sont dispatched
        $this->eventDispatcher
            ->expects($this->exactly(2))
            ->method('dispatch');

        $command = new LoginUserCommand('test@example.com', 'password123', '127.0.0.1');
        $result = $this->handler->handle($command);

        $this->assertTrue($result->isSuccess());
        $this->assertNotNull($result->getTwoFactorCode());
    }

    public function testFailedLoginIncrementsAttempts(): void
    {
        $user = new User(Uuid::v4(), 'test@example.com', password_hash('password123', PASSWORD_BCRYPT));
        
        $this->userRepository
            ->expects($this->once())
            ->method('findByEmail')
            ->willReturn($user);

        $this->userRepository
            ->expects($this->once())
            ->method('save')
            ->with($user);

        $this->loginAttemptRepository
            ->expects($this->once())
            ->method('save');

        // Event de failure dispatché
        $this->eventDispatcher
            ->expects($this->once())
            ->method('dispatch');

        $command = new LoginUserCommand('test@example.com', 'wrong_password', '127.0.0.1');
        $result = $this->handler->handle($command);

        $this->assertFalse($result->isSuccess());
        $this->assertEquals(1, $user->getFailedLoginAttempts());
    }

    public function testBlockedUserCannotLogin(): void
    {
        $user = new User(Uuid::v4(), 'test@example.com', password_hash('password123', PASSWORD_BCRYPT));
        
        // Bloquer le compte
        for ($i = 0; $i < 5; $i++) {
            $user->recordFailedLogin();
        }

        $this->userRepository
            ->expects($this->once())
            ->method('findByEmail')
            ->willReturn($user);

        $this->loginAttemptRepository
            ->expects($this->once())
            ->method('save');

        // Event de failure dispatché
        $this->eventDispatcher
            ->expects($this->once())
            ->method('dispatch');

        $command = new LoginUserCommand('test@example.com', 'password123', '127.0.0.1');
        $result = $this->handler->handle($command);

        $this->assertFalse($result->isSuccess());
        $this->assertEquals('Account is temporarily blocked', $result->getErrorMessage());
    }

    public function testNonExistentUserRecordsFailure(): void
    {
        $this->userRepository
            ->expects($this->once())
            ->method('findByEmail')
            ->willReturn(null);

        $this->loginAttemptRepository
            ->expects($this->once())
            ->method('save');

        // Event de failure dispatché
        $this->eventDispatcher
            ->expects($this->once())
            ->method('dispatch');

        $command = new LoginUserCommand('nonexistent@example.com', 'password', '127.0.0.1');
        $result = $this->handler->handle($command);

        $this->assertFalse($result->isSuccess());
    }
}