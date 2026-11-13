<?php

declare(strict_types=1);

namespace Nowo\PasswordStrengthBundle\Service;

use InvalidArgumentException;
use Nowo\PasswordStrengthBundle\Model\PasswordConditions;
use Nowo\PasswordStrengthBundle\Model\PolicyMode;

/**
 * Port: resolves the effective password conditions of a form field or constraint.
 *
 * The default implementation is {@see PolicyResolver}; override the alias of this interface to replace it.
 */
interface PolicyResolverInterface
{
    /**
     * @param array<string, mixed> $options Form type options
     *
     * @throws InvalidArgumentException When the profile is unknown or the inline conditions are missing/invalid
     */
    public function resolve(PolicyMode $mode, array $options): PasswordConditions;

    public function hasProfile(string $name): bool;

    /**
     * @return list<string>
     */
    public function profileNames(): array;
}
