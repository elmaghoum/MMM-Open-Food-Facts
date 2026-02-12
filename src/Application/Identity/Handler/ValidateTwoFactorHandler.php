<?php

declare(strict_types=1);

namespace Application\Identity\Handler;

use Application\Identity\Command\ValidateTwoFactorCommand;
use Application\Identity\DTO\TwoFactorValidationResult;
use Domain\Identity\Repository\TwoFactorCodeRepositoryInterface;
use Domain\Identity\Repository\UserRepositoryInterface;

final readonly class ValidateTwoFactorHandler
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private TwoFactorCodeRepositoryInterface $twoFactorCodeRepository,
    ) {
    }

    public function handle(ValidateTwoFactorCommand $command): TwoFactorValidationResult
    {
        $twoFactorCode = $this->twoFactorCodeRepository->findActiveByUserId($command->userId);

        if (!$twoFactorCode) {
            return TwoFactorValidationResult::invalid('No active 2FA code found');
        }

        try {
            $isValid = $twoFactorCode->validate($command->code);

            if (!$isValid) {
                return TwoFactorValidationResult::invalid('Invalid code');
            }

            // si le code est valide alors on reset les tentatives échouées
            $user = $this->userRepository->findById($command->userId);
            if ($user) {
                $user->recordSuccessfulLogin();
                $this->userRepository->save($user);
            }

            $this->twoFactorCodeRepository->save($twoFactorCode);

            return TwoFactorValidationResult::valid();

        } catch (\DomainException $e) {
            return TwoFactorValidationResult::invalid($e->getMessage());
        }
    }
}