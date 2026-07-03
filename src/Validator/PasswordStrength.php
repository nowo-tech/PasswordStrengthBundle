<?php

declare(strict_types=1);

namespace Nowo\PasswordStrengthBundle\Validator;

use Attribute;
use Symfony\Component\Validator\Constraint;

/**
 * Validates that a password meets configured strength requirements.
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_METHOD)]
final class PasswordStrength extends Constraint
{
    public const INVALID_STRENGTH = 'password_strength.invalid';

    public string $message = self::INVALID_STRENGTH;

    /** @var 'conditions'|'level' */
    public string $policyMode = 'level';

    public ?string $level = null;

    /** @var array<string, mixed>|null */
    public ?array $conditions = null;

    public function validatedBy(): string
    {
        return PasswordStrengthValidator::class;
    }
}
