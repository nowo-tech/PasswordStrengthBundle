<?php

declare(strict_types=1);

namespace Nowo\PasswordStrengthBundle\Profile;

use Nowo\PasswordStrengthBundle\DependencyInjection\Configuration;
use Nowo\PasswordStrengthBundle\Model\PasswordConditions;

use function array_keys;
use function array_map;
use function is_array;

/**
 * Default profile provider: the profiles from bundle configuration (`nowo_password_strength.profiles`, REQ-CFG-001).
 *
 * A profile is either the conditions map itself or an array with a `conditions` key.
 */
final readonly class ConfigPasswordProfileProvider implements PasswordProfileProviderInterface
{
    /**
     * @param array<string, array<string, mixed>> $levels Profiles map (`nowo_password_strength.profiles`)
     */
    public function __construct(
        private array $levels = Configuration::BUILTIN_PROFILES,
    ) {
    }

    public function get(string $name): ?PasswordConditions
    {
        if (!isset($this->levels[$name])) {
            return null;
        }

        $levelData = $this->levels[$name];

        return PasswordConditions::fromArray(is_array($levelData['conditions'] ?? null) ? $levelData['conditions'] : $levelData);
    }

    public function names(): array
    {
        return array_map(strval(...), array_keys($this->levels));
    }
}
