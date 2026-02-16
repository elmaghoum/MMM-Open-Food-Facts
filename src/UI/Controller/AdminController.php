<?php

declare(strict_types=1);

namespace UI\Controller;

use Domain\Identity\Entity\User;
use Domain\Identity\Repository\UserRepositoryInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Uid\Uuid;

#[Route('/admin')]
#[IsGranted('ROLE_ADMIN')]
final class AdminController extends AbstractController
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
    ) {
    }

    #[Route('', name: 'admin_dashboard')]
    public function index(): Response
    {
        $users = $this->userRepository->findAll();
        $totalUsers = $this->userRepository->countUsers();

        return $this->render('admin/index.html.twig', [
            'users' => $users,
            'totalUsers' => $totalUsers,
        ]);
    }

    #[Route('/user/create', name: 'admin_user_create', methods: ['POST'])]
    public function createUser(Request $request): Response
    {
        $email = $request->request->get('email');
        $password = $request->request->get('password');
        $role = $request->request->get('role', 'ROLE_USER');

        if (!$email || !$password) {
            $this->addFlash('error', 'Email et mot de passe requis');
            return $this->redirectToRoute('admin_dashboard');
        }

        if ($this->userRepository->findByEmail($email)) {
            $this->addFlash('error', 'Un utilisateur avec cet email existe déjà');
            return $this->redirectToRoute('admin_dashboard');
        }

        $roles = $role === 'ROLE_ADMIN' ? ['ROLE_ADMIN'] : ['ROLE_USER'];

        $user = new User(
            Uuid::v4(),
            $email,
            password_hash($password, PASSWORD_BCRYPT),
            $roles
        );

        $this->userRepository->save($user);

        $this->addFlash('success', sprintf('Utilisateur "%s" créé avec succès', $email));

        return $this->redirectToRoute('admin_dashboard');
    }

    #[Route('/user/{id}/toggle-active', name: 'admin_user_toggle_active', methods: ['POST'])]
    public function toggleActive(string $id): Response
    {
        $user = $this->userRepository->findById(Uuid::fromString($id));

        if (!$user) {
            $this->addFlash('error', 'Utilisateur non trouvé');
            return $this->redirectToRoute('admin_dashboard');
        }

        // Empêcher de désactiver son propre compte
        if ($user->getEmail() === $this->getUser()->getUserIdentifier()) {
            $this->addFlash('error', 'Vous ne pouvez pas désactiver votre propre compte');
            return $this->redirectToRoute('admin_dashboard');
        }

        if ($user->isActive()) {
            $user->deactivate();
            $this->addFlash('success', sprintf('Utilisateur "%s" désactivé', $user->getEmail()));
        } else {
            $user->activate();
            $this->addFlash('success', sprintf('Utilisateur "%s" activé', $user->getEmail()));
        }

        $this->userRepository->save($user);

        return $this->redirectToRoute('admin_dashboard');
    }

    #[Route('/user/{id}/delete', name: 'admin_user_delete', methods: ['DELETE'])]
    public function deleteUser(string $id): Response
    {
        $user = $this->userRepository->findById(Uuid::fromString($id));

        if (!$user) {
            return $this->json(['success' => false, 'error' => 'Utilisateur non trouvé'], 404);
        }

        // Empêcher de supprimer son propre compte
        if ($user->getEmail() === $this->getUser()->getUserIdentifier()) {
            return $this->json(['success' => false, 'error' => 'Vous ne pouvez pas supprimer votre propre compte'], 400);
        }

        $email = $user->getEmail();
        $this->userRepository->delete($user);

        return $this->json([
            'success' => true,
            'message' => sprintf('Utilisateur "%s" supprimé avec succès', $email)
        ]);
    }
}