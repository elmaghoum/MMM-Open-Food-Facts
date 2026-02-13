<?php

declare(strict_types=1);

namespace UI\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

final class LogoutController extends AbstractController
{
    #[Route('/logout', name: 'logout')]
    #[IsGranted('ROLE_USER')]
    public function logout(): void
    {
        throw new \LogicException('This method should never be reached!');
    }

    #[Route('/logout-success', name: 'logout_success')]
    public function logoutSuccess(): Response
    {
        return $this->render('logout/success.html.twig');
    }
}