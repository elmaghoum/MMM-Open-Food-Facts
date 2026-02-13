<?php

declare(strict_types=1);

namespace Application\Identity\Handler;

use Application\Identity\Command\LoginUserCommand;
use Application\Identity\DTO\LoginResult;
use Domain\Identity\Entity\LoginAttempt;
use Domain\Identity\Entity\TwoFactorCode;
use Domain\Identity\Event\LoginFailureEvent;
use Domain\Identity\Event\LoginSuccessEvent;
use Domain\Identity\Event\TwoFactorCodeGeneratedEvent;
use Domain\Identity\Repository\LoginAttemptRepositoryInterface;
use Domain\Identity\Repository\TwoFactorCodeRepositoryInterface;
use Domain\Identity\Repository\UserRepositoryInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

final readonly class LoginUserHandler
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private TwoFactorCodeRepositoryInterface $twoFactorCodeRepository,
        private LoginAttemptRepositoryInterface $loginAttemptRepository,
        private EventDispatcherInterface $eventDispatcher,
    ) {
    }

    public function handle(LoginUserCommand $command): LoginResult
    {
        $user = $this->userRepository->findByEmail($command->email);

        // User n'existe pas
        if (!$user) {
            $this->recordFailedAttempt($command->email, $command->ipAddress, 'User not found');
            $this->dispatchFailureEvent($command->email, $command->ipAddress, 'Invalid credentials');
            return LoginResult::failure('Invalid credentials');
        }

        // Vérifier si le compte est bloqué
        if ($user->isBlocked()) {
            $this->recordFailedAttempt($command->email, $command->ipAddress, 'Account blocked');
            $this->dispatchFailureEvent($command->email, $command->ipAddress, 'Account is temporarily blocked');
            return LoginResult::failure('Account is temporarily blocked');
        }

        // Vérifier le mot de passe
        if (!password_verify($command->password, $user->getPasswordHash())) {
            $user->recordFailedLogin();
            $this->userRepository->save($user);
            $this->recordFailedAttempt($command->email, $command->ipAddress, 'Invalid password');
            $this->dispatchFailureEvent($command->email, $command->ipAddress, 'Invalid credentials');
            return LoginResult::failure('Invalid credentials');
        }

        // Succès : générer le code 2FA
        $twoFactorCode = TwoFactorCode::generate($user->getId());
        $this->twoFactorCodeRepository->save($twoFactorCode);

        $this->userRepository->save($user);
        $this->recordSuccessAttempt($command->email, $command->ipAddress);

        // Dispatcher les events
        $this->dispatchSuccessEvent($user->getId(), $command->email, $command->ipAddress);
        $this->dispatchTwoFactorCodeEvent($command->email, $twoFactorCode);

        return LoginResult::success($user->getId(), $twoFactorCode);
    }

    private function recordFailedAttempt(string $email, string $ipAddress, string $reason): void
    {
        $attempt = LoginAttempt::recordFailure($email, $ipAddress);
        $this->loginAttemptRepository->save($attempt);
    }

    private function recordSuccessAttempt(string $email, string $ipAddress): void
    {
        $attempt = LoginAttempt::recordSuccess($email, $ipAddress);
        $this->loginAttemptRepository->save($attempt);
    }

    private function dispatchSuccessEvent(\Symfony\Component\Uid\Uuid $userId, string $email, string $ipAddress): void
    {
        $this->eventDispatcher->dispatch(
            new LoginSuccessEvent($userId, $email, $ipAddress)
        );
    }

    private function dispatchFailureEvent(string $email, string $ipAddress, string $reason): void
    {
        $this->eventDispatcher->dispatch(
            new LoginFailureEvent($email, $ipAddress, $reason)
        );
    }

    private function dispatchTwoFactorCodeEvent(string $email, TwoFactorCode $twoFactorCode): void
    {
        $this->eventDispatcher->dispatch(
            new TwoFactorCodeGeneratedEvent($email, $twoFactorCode)
        );
    }
}