<?php

declare(strict_types=1);

namespace UI\Controller;

use Application\Identity\Command\LoginUserCommand;
use Application\Identity\Handler\LoginUserHandler;
use Infrastructure\Security\TwoFactorSessionStorage;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use UI\Form\LoginType;

final class LoginController extends AbstractController
{
    public function __construct(
        private readonly LoginUserHandler $loginUserHandler,
        private readonly TwoFactorSessionStorage $twoFactorStorage,
    ) {
    }

    #[Route('/login', name: 'login')]
    public function login(Request $request): Response
    {
        // Si déjà connecté, rediriger selon le rôle
        if ($this->getUser()) {
            if (in_array('ROLE_ADMIN', $this->getUser()->getRoles(), true)) {
                return $this->redirectToRoute('admin_dashboard');
            }
            return $this->redirectToRoute('dashboard');
        }

        $form = $this->createForm(LoginType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();

            $command = new LoginUserCommand(
                email: $data['email'],
                password: $data['password'],
                ipAddress: $request->getClientIp() ?? '0.0.0.0'
            );

            $result = $this->loginUserHandler->handle($command);

            if ($result->isSuccess()) {
                // Stocker l'ID utilisateur en session pour la 2FA
                $this->twoFactorStorage->store($result->getUserId());
                $this->addFlash('success', 'Code de vérification envoyé par email !');
                return $this->redirectToRoute('two_factor');
            }

            $this->addFlash('error', $result->getErrorMessage());
        }

        return $this->render('login/index.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}