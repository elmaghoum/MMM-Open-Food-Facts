<?php

declare(strict_types=1);

namespace UI\Controller;

use Application\Identity\Command\ValidateTwoFactorCommand;
use Application\Identity\Handler\ValidateTwoFactorHandler;
use Domain\Identity\Repository\UserRepositoryInterface;
use Infrastructure\Security\TwoFactorSessionStorage;
use Infrastructure\Security\UserAdapter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use UI\Form\TwoFactorType;

final class TwoFactorController extends AbstractController
{
    public function __construct(
        private readonly ValidateTwoFactorHandler $validateTwoFactorHandler,
        private readonly TwoFactorSessionStorage $twoFactorStorage,
        private readonly UserRepositoryInterface $userRepository,
    ) {
    }

    #[Route('/two-factor', name: 'two_factor')]
    public function twoFactor(Request $request): Response
    {
        // Vérifier qu'on a bien un userId en session
        $userId = $this->twoFactorStorage->get();
        
        if (!$userId) {
            $this->addFlash('error', 'Session expirée. Veuillez vous reconnecter.');
            return $this->redirectToRoute('login');
        }

        $form = $this->createForm(TwoFactorType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();

            $command = new ValidateTwoFactorCommand(
                userId: $userId,
                code: $data['code']
            );

            $result = $this->validateTwoFactorHandler->handle($command);

            if ($result->isValid()) {
                // Code valide - créer la session utilisateur
                $domainUser = $this->userRepository->findById($userId);
                
                if ($domainUser) {
                    $userAdapter = UserAdapter::fromDomainUser($domainUser);
                    
                    // Créer la session Symfony Security manuellement
                    $token = new \Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken(
                        $userAdapter,
                        'main',
                        $userAdapter->getRoles()
                    );
                    
                    $this->container->get('security.token_storage')->setToken($token);
                    
                    // Sauvegarder la session
                    $request->getSession()->set('_security_main', serialize($token));

                    // Nettoyer la session 2FA
                    $this->twoFactorStorage->clear();

                    $this->addFlash('success', 'Connexion réussie !');
                    return $this->redirectToRoute('dashboard');
                }
            }

            $this->addFlash('error', 'Code incorrect ou expiré.');
        }

        return $this->render('two_factor/index.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}