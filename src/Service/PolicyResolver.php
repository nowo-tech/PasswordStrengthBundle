<?php

declare(strict_types=1);

namespace Nowo\PasswordStrengthBundle\Service;

use Nowo\PasswordStrengthBundle\Model\PasswordConditions;
use Nowo\PasswordStrengthBundle\Model\PolicyMode;

use function is_array;
use function is_string;

/**
 * Resolves effective password conditions from the configured profiles (REQ-CFG-001) or inline field options.
 *
 * The `level` form / constraint option names a profile; omitted, null or unknown names use the default profile.
 */
final readonly class PolicyResolver
{
    /**
     * @param array<string, array<string, mixed>> $levels Profiles map (`nowo_password_strength.profiles`)
     * @param string $defaultLevel Default profile name (`nowo_password_strength.default_profile`)
     */
    public function __construct(
        private array $levels = [],
        private string $defaultLevel = 'medium',
    ) {
    }

    /**
     * @param array<string, mixed> $options Form type options
     */
    public function resolve(PolicyMode $mode, array $options): PasswordConditions
    {
        if ($mode === PolicyMode::Conditions) {
            $inline = $options['conditions'] ?? null;

            return PasswordConditions::fromArray(is_array($inline) ? $inline : []);
        }

        $levelName = is_string($options['level'] ?? null) ? $options['level'] : $this->defaultLevel;
        $levelData = $this->levels[$levelName] ?? $this->levels[$this->defaultLevel] ?? $this->levels['medium'] ?? [];

        return PasswordConditions::fromArray(is_array($levelData['conditions'] ?? null) ? $levelData['conditions'] : $levelData);
    }
}
