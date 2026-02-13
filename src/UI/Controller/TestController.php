<?php

namespace UI\Controller;

use Infrastructure\Mail\TwoFactorMailer;
use Domain\Identity\Entity\TwoFactorCode;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Uid\Uuid;

class TestController extends AbstractController
{
    #[Route('/test/mail', name: 'test_mail')]
    public function testMail(TwoFactorMailer $mailer): Response
    {
        $code = TwoFactorCode::generate(Uuid::v4());
        
        try {
            $mailer->sendCode('tawesi7222@newtrea.com', $code);
            return new Response('Email envoyé avec succès ! Code: ' . $code->getCode());
        } catch (\Exception $e) {
            return new Response('Erreur : ' . $e->getMessage(), 500);
        }
    }
}