<?php

declare(strict_types=1);

namespace Nowo\PasswordStrengthBundle\Integration;

/**
 * Detects whether PasswordToggleBundle is installed at runtime.
 */
final class PasswordToggleIntegration
{
    public const TOGGLE_PASSWORD_TYPE = \Nowo\PasswordToggleBundle\Form\Type\PasswordType::class;

    public static function isAvailable(): bool
    {
        return class_exists(self::TOGGLE_PASSWORD_TYPE);
    }

    /**
     * @param class-string<\Symfony\Component\Form\AbstractType<mixed>> $parentFormType
     */
    public static function isToggleFormType(string $parentFormType): bool
    {
        return self::isAvailable() && $parentFormType === self::TOGGLE_PASSWORD_TYPE;
    }
}
