<?php

declare(strict_types=1);

namespace Nowo\PasswordStrengthBundle\Model;

use function array_diff;
use function array_keys;
use function array_values;
use function is_array;
use function is_int;
use function is_string;

/**
 * Normalized password policy conditions shared by PHP evaluator, validator, and frontend payload.
 */
final readonly class PasswordConditions
{
    /**
     * Every condition key accepted in profiles and inline `conditions` (short aliases included).
     *
     * @var list<string>
     */
    public const KNOWN_KEYS = [
        'min_length', 'max_length',
        'require_lowercase', 'require_uppercase', 'require_digit', 'require_special',
        'special_chars', 'disallow_whitespace', 'not_contain', 'min_unique_chars', 'regex', 'regex_message',
        'lowercase', 'uppercase', 'digit', 'special', 'no_whitespace',
    ];

    /**
     * @param list<string> $notContain
     */
    public function __construct(
        public int $minLength = 0,
        public ?int $maxLength = null,
        public bool $requireLowercase = false,
        public bool $requireUppercase = false,
        public bool $requireDigit = false,
        public bool $requireSpecial = false,
        public string $specialChars = '!@#$%^&*()_+-=[]{}|;:,.<>?',
        public bool $disallowWhitespace = false,
        public array $notContain = [],
        public ?string $regex = null,
        public ?string $regexMessage = null,
        public int $minUniqueChars = 0,
    ) {
    }

    /**
     * Keys of $data that are not condition keys (typos such as `min_lenght`).
     *
     * @param array<array-key, mixed> $data
     *
     * @return list<array-key>
     */
    public static function unknownKeys(array $data): array
    {
        return array_values(array_diff(array_keys($data), self::KNOWN_KEYS));
    }

    /**
     * Whether at least one rule is active. A policy without rules accepts every password, so profiles and inline
     * `conditions` must never resolve to one (`special_chars` / `regex_message` alone are not rules).
     */
    public function hasRules(): bool
    {
        return $this->minLength > 0
            || $this->maxLength !== null
            || $this->requireLowercase
            || $this->requireUppercase
            || $this->requireDigit
            || $this->requireSpecial
            || $this->disallowWhitespace
            || $this->notContain !== []
            || $this->regex !== null
            || $this->minUniqueChars > 0;
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
