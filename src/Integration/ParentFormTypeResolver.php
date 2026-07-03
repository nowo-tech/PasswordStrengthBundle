<?php

declare(strict_types=1);

namespace Nowo\PasswordStrengthBundle\Integration;

use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType as SymfonyPasswordType;

use function sprintf;

/**
 * Resolves which form type PasswordStrengthType extends as its parent.
 *
 * PasswordToggleBundle is optional: when not installed, Symfony PasswordType is used automatically.
 */
final class ParentFormTypeResolver
{
    public const SYMFONY_PASSWORD_TYPE = SymfonyPasswordType::class;

    /**
     * @return class-string<AbstractType<mixed>>
     */
    public static function resolve(?string $configuredParent, bool $usePasswordToggle): string
    {
        if ($configuredParent !== null && $configuredParent !== '') {
            self::assertValidFormType($configuredParent);

            return $configuredParent;
        }

        if ($usePasswordToggle && PasswordToggleIntegration::isAvailable()) {
            return PasswordToggleIntegration::TOGGLE_PASSWORD_TYPE;
        }

        return self::SYMFONY_PASSWORD_TYPE;
    }

    /**
     * Whether the toggle widget theme must be prepended for the resolved parent.
     *
     * @param class-string<AbstractType<mixed>> $resolvedParent
     */
    public static function shouldPrependToggleTheme(string $resolvedParent, bool $usePasswordToggle): bool
    {
        return $usePasswordToggle
            && PasswordToggleIntegration::isToggleFormType($resolvedParent)
            && PasswordToggleIntegration::isAvailable();
    }

    /**
     * @phpstan-assert class-string<\Symfony\Component\Form\AbstractType<mixed>> $class
     */
    private static function assertValidFormType(string $class): void
    {
        if (!class_exists($class)) {
            throw new InvalidConfigurationException(sprintf('The configured parent form type "%s" does not exist. Install the bundle that provides it or leave parent_form_type unset for automatic detection.', $class));
        }

        if (!is_subclass_of($class, AbstractType::class)) {
            throw new InvalidConfigurationException(sprintf('The configured parent form type "%s" must extend %s.', $class, AbstractType::class));
        }
    }
}
