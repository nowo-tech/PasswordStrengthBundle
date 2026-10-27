<?php

declare(strict_types=1);

namespace Nowo\PasswordStrengthBundle\Service;

use InvalidArgumentException;
use Nowo\PasswordStrengthBundle\DependencyInjection\Configuration;
use Nowo\PasswordStrengthBundle\Model\PasswordConditions;
use Nowo\PasswordStrengthBundle\Model\PolicyMode;

use function array_keys;
use function array_map;
use function implode;
use function is_array;
use function is_string;
use function sprintf;

/**
 * Resolves effective password conditions from the configured profiles (REQ-CFG-001) or inline field options.
 *
 * The `level` form / constraint option names a profile; omitted or null uses the default profile. A policy is never
 * weakened silently: an unknown profile name, inline conditions that are missing, carry unknown keys or hold no rule
 * throw an {@see InvalidArgumentException} (the form type reports it as an invalid option, the validator as a
 * constraint definition error).
 */
final readonly class PolicyResolver
{
    /**
     * @param array<string, array<string, mixed>> $levels Profiles map (`nowo_password_strength.profiles`)
     * @param string $defaultLevel Default profile name (`nowo_password_strength.default_profile`)
     */
    public function __construct(
        private array $levels = Configuration::BUILTIN_PROFILES,
        private string $defaultLevel = Configuration::DEFAULT_PROFILE,
    ) {
    }

    /**
     * @param array<string, mixed> $options Form type options
     *
     * @throws InvalidArgumentException When the profile is unknown or the inline conditions are missing/invalid
     */
    public function resolve(PolicyMode $mode, array $options): PasswordConditions
    {
        if ($mode === PolicyMode::Conditions) {
            return self::inlineConditions($options['conditions'] ?? null);
        }

        $levelName = is_string($options['level'] ?? null) ? $options['level'] : $this->defaultLevel;
        $levelData = $this->profile($levelName);

        return PasswordConditions::fromArray(is_array($levelData['conditions'] ?? null) ? $levelData['conditions'] : $levelData);
    }

    public function hasProfile(string $name): bool
    {
        return isset($this->levels[$name]);
    }

    /**
     * @return list<string>
     */
    public function profileNames(): array
    {
        return array_map(strval(...), array_keys($this->levels));
    }

    /**
     * Inline conditions for `policy_mode: conditions`: required, known keys only, at least one rule.
     *
     * @throws InvalidArgumentException
     */
    public static function inlineConditions(mixed $conditions): PasswordConditions
    {
        if (!is_array($conditions)) {
            throw new InvalidArgumentException('policy_mode "conditions" requires a non-empty "conditions" array.');
        }

        $unknown = PasswordConditions::unknownKeys($conditions);
        if ($unknown !== []) {
            throw new InvalidArgumentException(sprintf('Unknown password condition key(s) "%s". Known keys: %s.', implode('", "', array_map(strval(...), $unknown)), implode(', ', PasswordConditions::KNOWN_KEYS)));
        }

        /** @var array<string, mixed> $conditions */
        $resolved = PasswordConditions::fromArray($conditions);
        if (!$resolved->hasRules()) {
            throw new InvalidArgumentException('The inline password "conditions" hold no rule (e.g. min_length, require_digit): such a policy would accept every password.');
        }

        return $resolved;
    }

    /**
     * @throws InvalidArgumentException
     *
     * @return array<string, mixed>
     */
    private function profile(string $name): array
    {
        if (!isset($this->levels[$name])) {
            throw new InvalidArgumentException(sprintf('Unknown password strength profile "%s" (available: %s).', $name, implode(', ', $this->profileNames())));
        }

        return $this->levels[$name];
    }
}
