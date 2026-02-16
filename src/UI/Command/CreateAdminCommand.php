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
    name: 'app:create-admin',
    description: 'Créer un administrateur',
)]
final class CreateAdminCommand extends Command
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('email', InputArgument::REQUIRED, 'Email de l\'admin')
            ->addArgument('password', InputArgument::REQUIRED, 'Mot de passe');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $email = (string) $input->getArgument('email');
        $password = (string) $input->getArgument('password');

        // Vérifier si l'utilisateur existe déjà
        if ($this->userRepository->findByEmail($email)) {
            $io->error(sprintf('Un utilisateur avec l\'email "%s" existe déjà !', $email));
            return Command::FAILURE;
        }

        // Création de l'admin
        $admin = new User(
            id: Uuid::v4(),
            email: $email,
            passwordHash: password_hash($password, PASSWORD_BCRYPT),
            roles: ['ROLE_ADMIN']
        );

        $this->userRepository->save($admin);

        $io->success(sprintf('Administrateur "%s" créé avec succès !', $email));

        return Command::SUCCESS;
    }
}
