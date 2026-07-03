<?php

declare(strict_types=1);

namespace Nowo\PasswordStrengthBundle\Tests\Unit\Integration;

use Nowo\PasswordStrengthBundle\Integration\PasswordToggleIntegration;
use PHPUnit\Framework\TestCase;

final class PasswordToggleIntegrationTest extends TestCase
{
    public function testIsAvailableReflectsClassPresence(): void
    {
        self::assertSame(
            \class_exists(PasswordToggleIntegration::TOGGLE_PASSWORD_TYPE),
            PasswordToggleIntegration::isAvailable(),
        );
    }

    public function testIsToggleFormTypeRequiresMatchingParent(): void
    {
        self::assertFalse(PasswordToggleIntegration::isToggleFormType(
            \Symfony\Component\Form\Extension\Core\Type\PasswordType::class,
        ));

        if (!PasswordToggleIntegration::isAvailable()) {
            self::assertFalse(PasswordToggleIntegration::isToggleFormType(
                PasswordToggleIntegration::TOGGLE_PASSWORD_TYPE,
            ));

            return;
        }

        self::assertTrue(PasswordToggleIntegration::isToggleFormType(
            PasswordToggleIntegration::TOGGLE_PASSWORD_TYPE,
        ));
    }
}
