<?php

declare(strict_types=1);

namespace UI\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class HomeController extends AbstractController
{
    #[Route('/', name: 'home')]
    public function index(): Response
    {
        // Rediriger selon le rôle si connecté
        if ($this->getUser()) {
            $roles = $this->getUser()->getRoles();
            
            if (in_array('ROLE_ADMIN', $roles, true)) {
                return $this->redirectToRoute('admin_dashboard');
            }
            
            return $this->redirectToRoute('dashboard');
        }

        return $this->render('home/index.html.twig');
    }
}