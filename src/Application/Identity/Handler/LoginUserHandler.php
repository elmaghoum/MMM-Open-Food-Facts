<?php

declare(strict_types=1);

namespace Application\Identity\Handler;

use Application\Identity\Command\LoginUserCommand;
use Application\Identity\DTO\LoginResult;
use Domain\Identity\Entity\LoginAttempt;
use Domain\Identity\Entity\TwoFactorCode;
use Domain\Identity\Repository\LoginAttemptRepositoryInterface;
use Domain\Identity\Repository\TwoFactorCodeRepositoryInterface;
use Domain\Identity\Repository\UserRepositoryInterface;

final readonly class LoginUserHandler
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private TwoFactorCodeRepositoryInterface $twoFactorCodeRepository,
        private LoginAttemptRepositoryInterface $loginAttemptRepository,
    ) {
    }

    public function handle(LoginUserCommand $command): LoginResult
    {
        $user = $this->userRepository->findByEmail($command->email);

        // User n'existe pas
        if (!$user) {
            $this->recordFailedAttempt($command->email, $command->ipAddress);
            return LoginResult::failure('Invalid credentials');
        }

        // Vérifier si le compte est bloqué
        if ($user->isBlocked()) {
            $this->recordFailedAttempt($command->email, $command->ipAddress);
            return LoginResult::failure('Account is temporarily blocked');
        }

        // Vérifier le mot de passe
        if (!password_verify($command->password, $user->getPasswordHash())) {
            $user->recordFailedLogin();
            $this->userRepository->save($user);
            $this->recordFailedAttempt($command->email, $command->ipAddress);
            return LoginResult::failure('Invalid credentials');
        }

        // Succès : générer le code 2FA
        $twoFactorCode = TwoFactorCode::generate($user->getId());
        $this->twoFactorCodeRepository->save($twoFactorCode);

        $this->userRepository->save($user);
        $this->recordSuccessAttempt($command->email, $command->ipAddress);

        return LoginResult::success($user->getId(), $twoFactorCode);
    }

    private function recordFailedAttempt(string $email, string $ipAddress): void
    {
        $attempt = LoginAttempt::recordFailure($email, $ipAddress);
        $this->loginAttemptRepository->save($attempt);
    }

    private function recordSuccessAttempt(string $email, string $ipAddress): void
    {
        $attempt = LoginAttempt::recordSuccess($email, $ipAddress);
        $this->loginAttemptRepository->save($attempt);
    }
}