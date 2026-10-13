<?php

declare(strict_types=1);

namespace Nowo\PasswordStrengthBundle\Tests\Unit\Validator;

use Nowo\PasswordStrengthBundle\Service\PasswordPatternBuilder;
use Nowo\PasswordStrengthBundle\Service\PasswordStrengthEvaluator;
use Nowo\PasswordStrengthBundle\Service\PolicyResolver;
use Nowo\PasswordStrengthBundle\Validator\PasswordStrength;
use Nowo\PasswordStrengthBundle\Validator\PasswordStrengthValidator;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Translation\Loader\YamlFileLoader;
use Symfony\Component\Translation\Translator;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidatorFactory;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Exception\ConstraintDefinitionException;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;
use Symfony\Component\Validator\Validation;
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

    public function testViolationUsesMissingPlaceholder(): void
    {
        $builder = $this->createMock(ConstraintViolationBuilderInterface::class);
        $builder->expects(self::exactly(2))->method('setParameter')
            ->willReturnCallback(static function (string $key, string $value) use ($builder): ConstraintViolationBuilderInterface {
                self::assertContains($key, ['%missing%', '{{ missing }}']);
                self::assertSame('min_length, require_digit', $value);

                return $builder;
            });
        $builder->expects(self::once())->method('addViolation');

        $context = $this->createMock(ExecutionContextInterface::class);
        $context->expects(self::once())->method('buildViolation')
            ->with(PasswordStrength::INVALID_STRENGTH)
            ->willReturn($builder);

        $this->validator->initialize($context);
        $this->validator->validate('short', new PasswordStrength(level: 'medium'));
    }

    public function testRejectsUnknownPolicyModeWithClearException(): void
    {
        $this->expectException(ConstraintDefinitionException::class);
        $this->expectExceptionMessage('Invalid policyMode "levels"');

        $this->validator->validate('whatever', new PasswordStrength(policyMode: 'levels')); // @phpstan-ignore argument.type
    }

    public function testTranslatedMessageThroughValidatorsDomain(): void
    {
        // Regression: the default message key only existed in the NowoPasswordStrengthBundle domain and used
        // %missing% while the validator set {{ missing }}, so users saw the raw key.
        $translator = new Translator('en');
        $translator->addLoader('yaml', new YamlFileLoader());
        $translator->addResource('yaml', __DIR__ . '/../../../src/Resources/translations/validators.en.yaml', 'en', 'validators');

        $validator = Validation::createValidatorBuilder()
            ->setTranslator($translator)
            ->setTranslationDomain('validators')
            ->setConstraintValidatorFactory(new ConstraintValidatorFactory([PasswordStrengthValidator::class => $this->validator]))
            ->getValidator();

        $violations = $validator->validate('short', new PasswordStrength(policyMode: 'level', level: 'medium'));

        self::assertCount(1, $violations);
        self::assertSame(
            'The password does not meet the strength requirements (min_length, require_digit).',
            $violations->get(0)->getMessage(),
        );
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
