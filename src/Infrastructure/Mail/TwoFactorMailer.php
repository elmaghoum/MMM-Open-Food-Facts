<?php

declare(strict_types=1);

namespace Infrastructure\Mail;

use Domain\Identity\Entity\TwoFactorCode;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

final readonly class TwoFactorMailer
{
    public function __construct(
        private MailerInterface $mailer,
        private string $fromEmail = 'noreply.mieuxmangerenmarne@gmail.com',
    ) {
    }

    public function sendCode(string $recipientEmail, TwoFactorCode $twoFactorCode): void
    {
        // le to doit être dynamique (->to($recipientEmail)) mais pour les tests on peut le laisser en dur sur une adresse de test
        $email = (new Email())
            ->from($this->fromEmail)
            ->to('tawesi7222@newtrea.com') // remplacer par $recipientEmail en production // en test mettre une adresse de test
            ->subject('Votre Code de vérification - MMM')
            ->html($this->renderEmailTemplate($twoFactorCode));

        $this->mailer->send($email);
    }

    private function renderEmailTemplate(TwoFactorCode $twoFactorCode): string
    {
        $code = $twoFactorCode->getCode();
        $expiresAt = $twoFactorCode->getExpiresAt()->format('H:i:s');

        return <<<HTML
        <<!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <style>
                body {
                    font-family: Arial, sans-serif;
                    background-color: #f4f4f4;
                    margin: 0;
                    padding: 20px;
                }
                .container {
                    max-width: 600px;
                    margin: 0 auto;
                    background-color: white;
                    padding: 40px;
                    border-radius: 10px;
                    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
                }
                .code {
                    font-size: 36px;
                    font-weight: bold;
                    color: #000000;
                    text-align: center;
                    letter-spacing: 10px;
                    padding: 20px;
                    background-color: #f9f9f9;
                    border-radius: 5px;
                    margin: 30px 0;
                }
                .info {
                    color: #666;
                    font-size: 14px;
                    text-align: center;
                }
                h1 {
                    color: #333;
                    text-align: center;
                }
            </style>
        </head>
        <body>
            <div class="container">
                <h1>Votre code 2FA  -  Mieux Manger en Marne</h1>
                <p>Utilisez ce code pour finaliser votre connexion :</p>
                <div class="code">{$code}</div>
                <p class="info">Ce code expirera à {$expiresAt}</p>
                <p class="info">Si vous n'avez pas demandé ce code, veuillez ignorer cet email.</p>
            </div>
        </body>
        </html>
        HTML;
    }
}