<?php

declare(strict_types=1);

namespace Nowo\PasswordStrengthBundle\Validator;

use Nowo\PasswordStrengthBundle\Model\PolicyMode;
use Nowo\PasswordStrengthBundle\Service\PasswordStrengthEvaluator;
use Nowo\PasswordStrengthBundle\Service\PolicyResolver;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

use function is_string;

final class PasswordStrengthValidator extends ConstraintValidator
{
    public function __construct(
        private readonly PolicyResolver $policyResolver,
        private readonly PasswordStrengthEvaluator $evaluator,
    ) {
    }

    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof PasswordStrength) {
            throw new UnexpectedTypeException($constraint, PasswordStrength::class);
        }

        if ($value === null || $value === '') {
            return;
        }

        if (!is_string($value)) {
            throw new UnexpectedValueException($value, 'string');
        }

        $mode = PolicyMode::tryFrom($constraint->policyMode) ?? PolicyMode::Level;
        $conditions = $this->policyResolver->resolve($mode, [
            'level'      => $constraint->level,
            'conditions' => $constraint->conditions,
        ]);

        $result = $this->evaluator->evaluate($value, $conditions);

        if ($result->valid) {
            return;
        }

        $this->context->buildViolation($constraint->message)
            ->setParameter('{{ missing }}', implode(', ', $result->missing))
            ->addViolation();
    }
}
