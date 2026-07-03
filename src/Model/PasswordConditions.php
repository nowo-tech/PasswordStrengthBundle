<?php

declare(strict_types=1);

namespace Nowo\PasswordStrengthBundle\Model;

use function is_array;
use function is_bool;
use function is_int;
use function is_string;

/**
 * Normalized password policy conditions shared by PHP evaluator, validator, and frontend payload.
 */
final class PasswordConditions
{
    /**
     * @param list<string> $notContain
     */
    public function __construct(
        public readonly int $minLength = 0,
        public readonly ?int $maxLength = null,
        public readonly bool $requireLowercase = false,
        public readonly bool $requireUppercase = false,
        public readonly bool $requireDigit = false,
        public readonly bool $requireSpecial = false,
        public readonly string $specialChars = '!@#$%^&*()_+-=[]{}|;:,.<>?',
        public readonly bool $disallowWhitespace = false,
        public readonly array $notContain = [],
        public readonly ?string $regex = null,
        public readonly ?string $regexMessage = null,
        public readonly int $minUniqueChars = 0,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        $notContain = [];
        if (isset($data['not_contain']) && is_array($data['not_contain'])) {
            foreach ($data['not_contain'] as $item) {
                if (is_string($item) && $item !== '') {
                    $notContain[] = $item;
                }
            }
        }

        $minLength = isset($data['min_length']) && is_int($data['min_length']) ? $data['min_length'] : (int) ($data['min_length'] ?? 0);
        $maxLength = isset($data['max_length']) ? (int) $data['max_length'] : null;

        return new self(
            minLength: max(0, $minLength),
            maxLength: $maxLength !== null && $maxLength > 0 ? $maxLength : null,
            requireLowercase: self::boolValue($data, 'require_lowercase', 'lowercase'),
            requireUppercase: self::boolValue($data, 'require_uppercase', 'uppercase'),
            requireDigit: self::boolValue($data, 'require_digit', 'digit'),
            requireSpecial: self::boolValue($data, 'require_special', 'special'),
            specialChars: is_string($data['special_chars'] ?? null) && $data['special_chars'] !== ''
                ? $data['special_chars']
                : '!@#$%^&*()_+-=[]{}|;:,.<>?',
            disallowWhitespace: self::boolValue($data, 'disallow_whitespace', 'no_whitespace'),
            notContain: $notContain,
            regex: is_string($data['regex'] ?? null) && $data['regex'] !== '' ? $data['regex'] : null,
            regexMessage: is_string($data['regex_message'] ?? null) && $data['regex_message'] !== ''
                ? $data['regex_message']
                : null,
            minUniqueChars: isset($data['min_unique_chars']) ? max(0, (int) $data['min_unique_chars']) : 0,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'min_length'          => $this->minLength,
            'max_length'          => $this->maxLength,
            'require_lowercase'   => $this->requireLowercase,
            'require_uppercase'   => $this->requireUppercase,
            'require_digit'       => $this->requireDigit,
            'require_special'     => $this->requireSpecial,
            'special_chars'       => $this->specialChars,
            'disallow_whitespace' => $this->disallowWhitespace,
            'not_contain'         => $this->notContain,
            'regex'               => $this->regex,
            'regex_message'       => $this->regexMessage,
            'min_unique_chars'    => $this->minUniqueChars,
        ];
    }

    /**
     * @param array<string, mixed> $data
     */
    private static function boolValue(array $data, string $primary, string $alias): bool
    {
        if (isset($data[$primary])) {
            return (bool) $data[$primary];
        }

        if (isset($data[$alias])) {
            return (bool) $data[$alias];
        }

        return false;
    }
}
