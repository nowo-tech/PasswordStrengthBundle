<?php

declare(strict_types=1);

namespace Nowo\PasswordStrengthBundle\Integration;

use Nowo\PasswordToggleBundle\Form\Type\PasswordType;
use Symfony\Component\Form\AbstractType;

/**
 * Detects whether PasswordToggleBundle is installed at runtime.
 */
final class PasswordToggleIntegration
{
    public const TOGGLE_PASSWORD_TYPE = PasswordType::class;

    public static function isAvailable(): bool
    {
        return class_exists(self::TOGGLE_PASSWORD_TYPE);
    }

    /**
     * @param class-string<AbstractType<mixed>> $parentFormType
     */
    public static function isToggleFormType(string $parentFormType): bool
    {
        return self::isAvailable() && $parentFormType === self::TOGGLE_PASSWORD_TYPE;
    }
}
