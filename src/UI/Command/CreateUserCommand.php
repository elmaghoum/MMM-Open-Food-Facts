<?php

declare(strict_types=1);

namespace UI\Command;

use Domain\Identity\Entity\User;
use Domain\Identity\Repository\UserRepositoryInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Uid\Uuid;

#[AsCommand(
    name: 'app:create-user',
    description: 'Créer un utilisateur de test',
)]
final class CreateUserCommand extends Command
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('email', InputArgument::REQUIRED, 'Email de l\'utilisateur')
            ->addArgument('password', InputArgument::REQUIRED, 'Mot de passe');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $email = $input->getArgument('email');
        $password = $input->getArgument('password');

        // Vérifier si l'utilisateur existe déjà
        $existingUser = $this->userRepository->findByEmail($email);
        if ($existingUser) {
            $io->error(sprintf('Un utilisateur avec l\'email "%s" existe déjà !', $email));
            return Command::FAILURE;
        }

        // Créer l'utilisateur
        $user = new User(
            id: Uuid::v4(),
            email: $email,
            passwordHash: password_hash($password, PASSWORD_BCRYPT)
        );

        $this->userRepository->save($user);

        $io->success(sprintf('Utilisateur "%s" créé avec succès !', $email));
        $io->info('Email: ' . $email);
        $io->info('Mot de passe: ' . $password);

        return Command::SUCCESS;
    }
}