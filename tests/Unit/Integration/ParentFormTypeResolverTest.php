<?php

declare(strict_types=1);

namespace Nowo\PasswordStrengthBundle\Tests\Unit\Integration;

use Nowo\PasswordStrengthBundle\Integration\ParentFormTypeResolver;
use Nowo\PasswordStrengthBundle\Integration\PasswordToggleIntegration;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Form\Extension\Core\Type\PasswordType as SymfonyPasswordType;

final class ParentFormTypeResolverTest extends TestCase
{
    public function testAutoResolvesSymfonyPasswordTypeWithoutToggleBundle(): void
    {
        if (PasswordToggleIntegration::isAvailable()) {
            self::markTestSkipped('PasswordToggleBundle is installed in this environment.');
        }

        self::assertSame(
            SymfonyPasswordType::class,
            ParentFormTypeResolver::resolve(null, true),
        );
        self::assertSame(
            SymfonyPasswordType::class,
            ParentFormTypeResolver::resolve(null, false),
        );
    }

    public function testAutoResolvesToggleParentWhenBundleIsAvailable(): void
    {
        if (!PasswordToggleIntegration::isAvailable()) {
            self::markTestSkipped('PasswordToggleBundle is not installed in this environment.');
        }

        self::assertSame(
            PasswordToggleIntegration::TOGGLE_PASSWORD_TYPE,
            ParentFormTypeResolver::resolve(null, true),
        );
        self::assertSame(
            SymfonyPasswordType::class,
            ParentFormTypeResolver::resolve(null, false),
        );
    }

    public function testExplicitParentOverridesAutoDetection(): void
    {
        self::assertSame(
            SymfonyPasswordType::class,
            ParentFormTypeResolver::resolve(SymfonyPasswordType::class, true),
        );
    }

    public function testInvalidParentFormTypeThrows(): void
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage('does not exist');

        ParentFormTypeResolver::resolve('App\\Form\\NonExistentPasswordType', true);
    }

    public function testShouldPrependToggleThemeOnlyForToggleParent(): void
    {
        self::assertFalse(ParentFormTypeResolver::shouldPrependToggleTheme(SymfonyPasswordType::class, true));

        if (!PasswordToggleIntegration::isAvailable()) {
            self::assertFalse(ParentFormTypeResolver::shouldPrependToggleTheme(
                PasswordToggleIntegration::TOGGLE_PASSWORD_TYPE,
                true,
            ));

            return;
        }

        self::assertTrue(ParentFormTypeResolver::shouldPrependToggleTheme(
            PasswordToggleIntegration::TOGGLE_PASSWORD_TYPE,
            true,
        ));
        self::assertFalse(ParentFormTypeResolver::shouldPrependToggleTheme(
            PasswordToggleIntegration::TOGGLE_PASSWORD_TYPE,
            false,
        ));
    }
}
