<?php

declare(strict_types=1);

namespace Nowo\PasswordStrengthBundle\Service;

use Nowo\PasswordStrengthBundle\Model\PasswordConditions;
use Nowo\PasswordStrengthBundle\Model\PolicyMode;

use function is_array;
use function is_string;

/**
 * Resolves effective password conditions from global levels or inline field options.
 */
final readonly class PolicyResolver
{
    /**
     * @param array<string, array<string, mixed>> $levels
     */
    public function __construct(
        private array $levels = [],
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

        $levelName = is_string($options['level'] ?? null) ? $options['level'] : 'medium';
        $levelData = $this->levels[$levelName] ?? $this->levels['medium'] ?? [];

        return PasswordConditions::fromArray(is_array($levelData['conditions'] ?? null) ? $levelData['conditions'] : $levelData);
    }
}
