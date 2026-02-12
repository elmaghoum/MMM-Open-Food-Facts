<?php

declare(strict_types=1);

namespace Infrastructure\EventSubscriber;

use Domain\Identity\Event\LoginFailureEvent;
use Domain\Identity\Event\LoginSuccessEvent;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final readonly class LoginEventSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private LoggerInterface $logger,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            LoginSuccessEvent::class => 'onLoginSuccess',
            LoginFailureEvent::class => 'onLoginFailure',
        ];
    }

    public function onLoginSuccess(LoginSuccessEvent $event): void
    {
        $this->logger->info('Login successful', [
            'user_id' => $event->userId->toRfc4122(),
            'email' => $event->email,
            'ip' => $event->ipAddress,
        ]);
    }

    public function onLoginFailure(LoginFailureEvent $event): void
    {
        $this->logger->warning('Login failed', [
            'email' => $event->email,
            'ip' => $event->ipAddress,
            'reason' => $event->reason,
        ]);
    }
}