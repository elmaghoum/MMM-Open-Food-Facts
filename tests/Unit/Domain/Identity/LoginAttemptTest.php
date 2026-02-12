<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Identity;

use Domain\Identity\Entity\LoginAttempt;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

final class LoginAttemptTest extends TestCase
{
    public function testSuccessfulLoginAttemptCanBeRecorded(): void
    {
        $attempt = LoginAttempt::recordSuccess(
            email: 'test@example.com',
            ipAddress: '192.168.1.1'
        );

        $this->assertInstanceOf(LoginAttempt::class, $attempt);
        $this->assertEquals('test@example.com', $attempt->getEmail());
        $this->assertTrue($attempt->isSuccess());
        $this->assertEquals('192.168.1.1', $attempt->getIpAddress());
    }

    public function testFailedLoginAttemptCanBeRecorded(): void
    {
        $attempt = LoginAttempt::recordFailure(
            email: 'test@example.com',
            ipAddress: '192.168.1.1'
        );

        $this->assertInstanceOf(LoginAttempt::class, $attempt);
        $this->assertFalse($attempt->isSuccess());
    }
}