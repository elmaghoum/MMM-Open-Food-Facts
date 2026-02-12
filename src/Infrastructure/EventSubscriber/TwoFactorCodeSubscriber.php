<?php

declare(strict_types=1);

namespace Infrastructure\EventSubscriber;

use Domain\Identity\Event\TwoFactorCodeGeneratedEvent;
use Infrastructure\Mail\TwoFactorMailer;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final readonly class TwoFactorCodeSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private TwoFactorMailer $mailer,
        private LoggerInterface $logger,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            TwoFactorCodeGeneratedEvent::class => 'onTwoFactorCodeGenerated',
        ];
    }

    public function onTwoFactorCodeGenerated(TwoFactorCodeGeneratedEvent $event): void
    {
        try {
            $this->mailer->sendCode($event->email, $event->twoFactorCode);
            
            $this->logger->info('2FA code sent', [
                'email' => $event->email,
                'code_id' => $event->twoFactorCode->getId()->toRfc4122(),
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Failed to send 2FA code', [
                'email' => $event->email,
                'error' => $e->getMessage(),
            ]);
            
            throw $e;
        }
    }
}