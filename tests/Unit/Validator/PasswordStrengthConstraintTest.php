<?php

declare(strict_types=1);

namespace Nowo\PasswordStrengthBundle\Tests\Unit\Validator;

use Nowo\PasswordStrengthBundle\Validator\PasswordStrength;
use Nowo\PasswordStrengthBundle\Validator\PasswordStrengthValidator;
use PHPUnit\Framework\TestCase;

final class PasswordStrengthConstraintTest extends TestCase
{
    public function testValidatedBy(): void
    {
        self::assertSame(PasswordStrengthValidator::class, (new PasswordStrength())->validatedBy());
    }
}
