<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Identity;

use Application\Identity\Command\ValidateTwoFactorCommand;
use Application\Identity\Handler\ValidateTwoFactorHandler;
use Domain\Identity\Entity\User;
use Domain\Identity\Entity\TwoFactorCode;
use Domain\Identity\Repository\UserRepositoryInterface;
use Domain\Identity\Repository\TwoFactorCodeRepositoryInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

final class ValidateTwoFactorHandlerTest extends TestCase
{
    /** @var UserRepositoryInterface&MockObject */
    private $userRepository;

    /** @var TwoFactorCodeRepositoryInterface&MockObject */
    private $twoFactorCodeRepository;

    private ValidateTwoFactorHandler $handler;

    protected function setUp(): void
    {
        $this->userRepository = $this->createMock(UserRepositoryInterface::class);
        $this->twoFactorCodeRepository = $this->createMock(TwoFactorCodeRepositoryInterface::class);

        $this->handler = new ValidateTwoFactorHandler(
            $this->userRepository,
            $this->twoFactorCodeRepository
        );
    }

    public function testValidCodeResetsFailedAttempts(): void
    {
        $userId = Uuid::v4();

        $user = new User($userId, 'test@example.com', 'hash');
        $user->recordFailedLogin();
        $user->recordFailedLogin();

        $twoFactorCode = TwoFactorCode::generate($userId);
        $code = $twoFactorCode->getCode();

        $this->twoFactorCodeRepository
            ->expects($this->once())
            ->method('findActiveByUserId')
            ->with($userId)
            ->willReturn($twoFactorCode);

        $this->userRepository
            ->expects($this->once())
            ->method('findById')
            ->with($userId)
            ->willReturn($user);

        $this->userRepository
            ->expects($this->once())
            ->method('save')
            ->with($user);

        $this->twoFactorCodeRepository
            ->expects($this->once())
            ->method('save')
            ->with($twoFactorCode);

        $command = new ValidateTwoFactorCommand($userId, $code);
        $result = $this->handler->handle($command);

        $this->assertTrue($result->isValid());
        $this->assertSame(0, $user->getFailedLoginAttempts());
    }

    public function testInvalidCodeReturnsFailure(): void
    {
        $userId = Uuid::v4();
        $twoFactorCode = TwoFactorCode::generate($userId);

        $this->twoFactorCodeRepository
            ->expects($this->once())
            ->method('findActiveByUserId')
            ->with($userId)
            ->willReturn($twoFactorCode);

        // Le user ne doit PAS être chargé si le code est invalide
        $this->userRepository
            ->expects($this->never())
            ->method('findById');

        // Rien ne doit être sauvegardé
        $this->userRepository
            ->expects($this->never())
            ->method('save');

        $this->twoFactorCodeRepository
            ->expects($this->never())
            ->method('save');

        $command = new ValidateTwoFactorCommand($userId, '000000');

        $result = $this->handler->handle($command);

        $this->assertFalse($result->isValid());
    }

    public function testNoCodeFoundReturnsFailure(): void
    {
        $userId = Uuid::v4();

        $this->twoFactorCodeRepository
            ->expects($this->once())
            ->method('findActiveByUserId')
            ->with($userId)
            ->willReturn(null);

        // Aucun appel supplémentaire ne doit être fait
        $this->userRepository
            ->expects($this->never())
            ->method('findById');

        $this->userRepository
            ->expects($this->never())
            ->method('save');

        $this->twoFactorCodeRepository
            ->expects($this->never())
            ->method('save');

        $command = new ValidateTwoFactorCommand($userId, '123456');

        $result = $this->handler->handle($command);

        $this->assertFalse($result->isValid());
    }
}
