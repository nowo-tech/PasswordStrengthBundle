<?php

declare(strict_types=1);

namespace Nowo\PasswordStrengthBundle\Validator;

use Attribute;
use Symfony\Component\Validator\Attribute\HasNamedArguments;
use Symfony\Component\Validator\Constraint;

/**
 * Validates that a password meets configured strength requirements.
 *
 * Usage: `#[PasswordStrength(policyMode: 'level', level: 'strong')]` or
 * `#[PasswordStrength(policyMode: 'conditions', conditions: ['min_length' => 12])]`.
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_METHOD)]
final class PasswordStrength extends Constraint
{
    public const INVALID_STRENGTH = 'password_strength.invalid';

    /**
     * @param array<string, mixed>|null $options Legacy options array (deprecated by Symfony 7.4+; prefer named arguments)
     * @param 'conditions'|'level' $policyMode
     * @param string|null $level Profile name; null = bundle `default_profile`
     * @param array<string, mixed>|null $conditions Inline conditions when $policyMode is 'conditions'
     * @param list<string>|null $groups
     */
    #[HasNamedArguments]
    public function __construct(
        ?array $options = null,
        public string $policyMode = 'level',
        public ?string $level = null,
        public ?array $conditions = null,
        public string $message = self::INVALID_STRENGTH,
        ?array $groups = null,
        mixed $payload = null,
    ) {
        parent::__construct($options, $groups, $payload);
    }

    public function validatedBy(): string
    {
        return PasswordStrengthValidator::class;
    }
}
