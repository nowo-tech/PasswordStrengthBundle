<?php

declare(strict_types=1);

namespace Nowo\PasswordStrengthBundle\Tests\Unit\Validator;

use Nowo\PasswordStrengthBundle\Service\PasswordPatternBuilder;
use Nowo\PasswordStrengthBundle\Service\PasswordStrengthEvaluator;
use Nowo\PasswordStrengthBundle\Service\PolicyResolver;
use Nowo\PasswordStrengthBundle\Validator\PasswordStrength;
use Nowo\PasswordStrengthBundle\Validator\PasswordStrengthValidator;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;
use Symfony\Component\Validator\Violation\ConstraintViolationBuilderInterface;

final class PasswordStrengthValidatorTest extends TestCase
{
    private PasswordStrengthValidator $validator;

    protected function setUp(): void
    {
        $this->validator = new PasswordStrengthValidator(
            new PolicyResolver(['medium' => ['min_length' => 8, 'require_digit' => true]]),
            new PasswordStrengthEvaluator(new PasswordPatternBuilder()),
        );
    }

    public function testAddsViolationWhenPasswordIsWeak(): void
    {
        $constraint             = new PasswordStrength();
        $constraint->policyMode = 'level';
        $constraint->level      = 'medium';

        $builder = $this->createMock(ConstraintViolationBuilderInterface::class);
        $builder->method('setParameter')->willReturnSelf();
        $builder->expects(self::once())->method('addViolation');

        $context = $this->createMock(ExecutionContextInterface::class);
        $context->expects(self::once())->method('buildViolation')->willReturn($builder);

        $this->validator->initialize($context);
        $this->validator->validate('short', $constraint);
    }

    public function testAcceptsValidPassword(): void
    {
        $constraint             = new PasswordStrength();
        $constraint->policyMode = 'level';
        $constraint->level      = 'medium';

        $context = $this->createMock(ExecutionContextInterface::class);
        $context->expects(self::never())->method('buildViolation');

        $this->validator->initialize($context);
        $this->validator->validate('longpass1', $constraint);
    }

    public function testSkipsNullAndEmptyValues(): void
    {
        $constraint = new PasswordStrength();
        $context    = $this->createMock(ExecutionContextInterface::class);
        $context->expects(self::never())->method('buildViolation');

        $this->validator->initialize($context);
        $this->validator->validate(null, $constraint);
        $this->validator->validate('', $constraint);
    }

    public function testRejectsNonStringValue(): void
    {
        $this->expectException(UnexpectedValueException::class);

        $this->validator->validate(123, new PasswordStrength());
    }

    public function testRejectsWrongConstraintType(): void
    {
        $this->expectException(UnexpectedTypeException::class);

        $wrong = new class extends Constraint {
        };

        $this->validator->validate('value', $wrong);
    }

    public function testValidatesInlineConditions(): void
    {
        $constraint             = new PasswordStrength();
        $constraint->policyMode = 'conditions';
        $constraint->conditions = ['min_length' => 12, 'require_special' => true];

        $builder = $this->createMock(ConstraintViolationBuilderInterface::class);
        $builder->method('setParameter')->willReturnSelf();
        $builder->expects(self::once())->method('addViolation');

        $context = $this->createMock(ExecutionContextInterface::class);
        $context->expects(self::once())->method('buildViolation')->willReturn($builder);

        $this->validator->initialize($context);
        $this->validator->validate('short', $constraint);
    }
}
