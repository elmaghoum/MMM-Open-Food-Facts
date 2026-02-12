<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Identity;

use Domain\Identity\Entity\TwoFactorCode;
use Domain\Identity\Exception\TwoFactorCodeExpiredException;
use Domain\Identity\Exception\TwoFactorCodeAlreadyUsedException;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

final class TwoFactorCodeTest extends TestCase
{
    public function testTwoFactorCodeCanBeCreated(): void
    {
        $userId = Uuid::v4();
        $code = TwoFactorCode::generate($userId);

        $this->assertInstanceOf(TwoFactorCode::class, $code);
        $this->assertEquals($userId, $code->getUserId());
        $this->assertMatchesRegularExpression('/^\d{6}$/', $code->getCode());
        $this->assertNull($code->getUsedAt());
    }

    public function testCodeExpiresAfter10Minutes(): void
    {
        $code = TwoFactorCode::generate(Uuid::v4());

        $this->assertFalse($code->isExpired());

        // 10 minutes + 1 seconde plus tard
        $futureTime = (new \DateTimeImmutable())->modify('+10 minutes +1 second');
        $this->assertTrue($code->isExpired($futureTime));
    }

    public function testValidCodeCanBeMarkedAsUsed(): void
    {
        $code = TwoFactorCode::generate(Uuid::v4());

        $this->assertFalse($code->isUsed());

        $code->markAsUsed();

        $this->assertTrue($code->isUsed());
        $this->assertNotNull($code->getUsedAt());
    }

    public function testExpiredCodeCannotBeValidated(): void
    {
        $this->expectException(TwoFactorCodeExpiredException::class);

        $code = TwoFactorCode::generate(Uuid::v4());
        
        $futureTime = (new \DateTimeImmutable())->modify('+11 minutes');
        $code->validate('123456', $futureTime);
    }

    public function testUsedCodeCannotBeValidatedAgain(): void
    {
        $this->expectException(TwoFactorCodeAlreadyUsedException::class);

        $code = TwoFactorCode::generate(Uuid::v4());
        $generatedCode = $code->getCode();
        
        $code->markAsUsed();
        $code->validate($generatedCode);
    }

    public function testValidateWithCorrectCode(): void
    {
        $code = TwoFactorCode::generate(Uuid::v4());
        $generatedCode = $code->getCode();

        $isValid = $code->validate($generatedCode);

        $this->assertTrue($isValid);
        $this->assertTrue($code->isUsed());
    }

    public function testValidateWithIncorrectCode(): void
    {
        $code = TwoFactorCode::generate(Uuid::v4());

        $isValid = $code->validate('000000');

        $this->assertFalse($isValid);
        $this->assertFalse($code->isUsed());
    }

    public function testCodeIs6Digits(): void
    {
        for ($i = 0; $i < 10; $i++) {
            $code = TwoFactorCode::generate(Uuid::v4());
            $this->assertEquals(6, strlen($code->getCode()));
            $this->assertMatchesRegularExpression('/^\d{6}$/', $code->getCode());
        }
    }
}