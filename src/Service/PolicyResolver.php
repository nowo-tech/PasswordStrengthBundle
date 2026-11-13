<?php

declare(strict_types=1);

namespace Nowo\PasswordStrengthBundle\Service;

use InvalidArgumentException;
use Nowo\PasswordStrengthBundle\DependencyInjection\Configuration;
use Nowo\PasswordStrengthBundle\Model\PasswordConditions;
use Nowo\PasswordStrengthBundle\Model\PolicyMode;
use Nowo\PasswordStrengthBundle\Profile\ConfigPasswordProfileProvider;
use Nowo\PasswordStrengthBundle\Profile\PasswordProfileProviderInterface;

use function array_map;
use function implode;
use function is_array;
use function is_string;
use function sprintf;

/**
 * Resolves effective password conditions from the named profiles (REQ-CFG-001) or inline field options.
 *
 * Named profiles come from {@see PasswordProfileProviderInterface}; the default provider reads the bundle configuration.
 *
 * The `level` form / constraint option names a profile; omitted or null uses the default profile. A policy is never
 * weakened silently: an unknown profile name, a profile that holds no rule, inline conditions that are missing, carry
 * unknown keys or hold no rule throw an {@see InvalidArgumentException} (the form type reports it as an invalid option, the validator as a
 * constraint definition error).
 */
final readonly class PolicyResolver implements PolicyResolverInterface
{
    private PasswordProfileProviderInterface $profiles;

    /**
     * @param array<string, array<string, mixed>> $levels Profiles map (`nowo_password_strength.profiles`); used only
     *                                                    when no profile provider is given
     * @param string $defaultLevel Default profile name (`nowo_password_strength.default_profile`)
     * @param PasswordProfileProviderInterface|null $profiles Source of the named profiles (the container passes the
     *                                                        `PasswordProfileProviderInterface` alias)
     */
    public function __construct(
        array $levels = Configuration::BUILTIN_PROFILES,
        private string $defaultLevel = Configuration::DEFAULT_PROFILE,
        ?PasswordProfileProviderInterface $profiles = null,
    ) {
        $this->profiles = $profiles ?? new ConfigPasswordProfileProvider($levels);
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

        $conditions = $this->profiles->get($levelName)
            ?? throw new InvalidArgumentException(sprintf('Unknown password strength profile "%s" (available: %s).', $levelName, implode(', ', $this->profileNames())));

        // The configured map is checked at compile time; a provider's profile (an empty row, a typo in a key that
        // fromArray() drops) is checked here, so a profile never silently accepts every password.
        if (!$conditions->hasRules()) {
            throw new InvalidArgumentException(sprintf('The password strength profile "%s" holds no rule (e.g. min_length, require_digit): such a policy would accept every password.', $levelName));
        }

        return $conditions;
    }

    public function hasProfile(string $name): bool
    {
        return $this->profiles->get($name) instanceof PasswordConditions;
    }

    /**
     * @return list<string>
     */
    public function profileNames(): array
    {
        return $this->profiles->names();
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
}
