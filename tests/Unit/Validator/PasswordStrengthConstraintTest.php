<?php

declare(strict_types=1);

namespace Nowo\PasswordStrengthBundle\Tests\Unit\Validator;

use Nowo\PasswordStrengthBundle\Validator\PasswordStrength;
use Nowo\PasswordStrengthBundle\Validator\PasswordStrengthValidator;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;

final class PasswordStrengthConstraintTest extends TestCase
{
    #[PasswordStrength(policyMode: 'conditions', conditions: ['min_length' => 12], message: 'custom', groups: ['registration'], payload: 'p')]
    private string $annotated = 'Secret123';

    public function testValidatedBy(): void
    {
        self::assertSame(PasswordStrengthValidator::class, (new PasswordStrength())->validatedBy());
    }

    public function testDefaults(): void
    {
        $constraint = new PasswordStrength();

        self::assertSame('level', $constraint->policyMode);
        self::assertNull($constraint->level);
        self::assertNull($constraint->conditions);
        self::assertSame(PasswordStrength::INVALID_STRENGTH, $constraint->message);
        self::assertSame(['Default'], $constraint->groups);
    }

    public function testNamedArgumentsAsDocumented(): void
    {
        // Regression: #[PasswordStrength(policyMode: 'level', level: 'medium')] used to fail with "Unknown named parameter".
        $constraint = new PasswordStrength(policyMode: 'level', level: 'medium');

        self::assertSame('level', $constraint->policyMode);
        self::assertSame('medium', $constraint->level);
    }

    public function testAttributeInstantiation(): void
    {
        $attributes = (new ReflectionProperty(self::class, 'annotated'))->getAttributes(PasswordStrength::class);
        self::assertCount(1, $attributes);

        $constraint = $attributes[0]->newInstance();
        self::assertInstanceOf(PasswordStrength::class, $constraint);
        self::assertSame('conditions', $constraint->policyMode);
        self::assertSame(['min_length' => 12], $constraint->conditions);
        self::assertSame('custom', $constraint->message);
        self::assertSame(['registration'], $constraint->groups);
        self::assertSame('p', $constraint->payload);
        self::assertSame('Secret123', $this->annotated);
    }
}
