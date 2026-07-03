<?php

declare(strict_types=1);

namespace Nowo\PasswordStrengthBundle\Tests\Unit\Validator;

use Nowo\PasswordStrengthBundle\Service\PasswordPatternBuilder;
use Nowo\PasswordStrengthBundle\Service\PasswordStrengthEvaluator;
use Nowo\PasswordStrengthBundle\Service\PolicyResolver;
use Nowo\PasswordStrengthBundle\Validator\PasswordStrength;
use Nowo\PasswordStrengthBundle\Validator\PasswordStrengthValidator;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Violation\ConstraintViolationBuilderInterface;

final class PasswordStrengthValidatorTest extends TestCase
{
    public function testAddsViolationWhenPasswordIsWeak(): void
    {
        $validator = new PasswordStrengthValidator(
            new PolicyResolver(['medium' => ['min_length' => 8, 'require_digit' => true]]),
            new PasswordStrengthEvaluator(new PasswordPatternBuilder()),
        );

        $constraint = new PasswordStrength();
        $constraint->policyMode = 'level';
        $constraint->level = 'medium';

        $builder = $this->createMock(ConstraintViolationBuilderInterface::class);
        $builder->method('setParameter')->willReturnSelf();
        $builder->expects(self::once())->method('addViolation');

        $context = $this->createMock(ExecutionContextInterface::class);
        $context->expects(self::once())->method('buildViolation')->willReturn($builder);

        $validator->initialize($context);
        $validator->validate('short', $constraint);
    }

    public function testAcceptsValidPassword(): void
    {
        $validator = new PasswordStrengthValidator(
            new PolicyResolver(['medium' => ['min_length' => 8, 'require_digit' => true]]),
            new PasswordStrengthEvaluator(new PasswordPatternBuilder()),
        );

        $constraint = new PasswordStrength();
        $constraint->policyMode = 'level';
        $constraint->level = 'medium';
        $context = $this->createMock(ExecutionContextInterface::class);
        $context->expects(self::never())->method('buildViolation');

        $validator->initialize($context);
        $validator->validate('longpass1', $constraint);
    }
}
