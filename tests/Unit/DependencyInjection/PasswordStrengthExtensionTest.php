<?php

declare(strict_types=1);

namespace Nowo\PasswordStrengthBundle\Tests\Unit\DependencyInjection;

use Nowo\PasswordStrengthBundle\DependencyInjection\PasswordStrengthExtension;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\TwigBundle\DependencyInjection\TwigExtension;
use Symfony\Component\DependencyInjection\ContainerBuilder;

final class PasswordStrengthExtensionTest extends TestCase
{
    public function testLoadSetsParameters(): void
    {
        $container = new ContainerBuilder();
        $extension = new PasswordStrengthExtension();

        $extension->load([[
            'form_theme'          => 'bootstrap_5_layout.html.twig',
            'feedback_position'   => 'above',
            'show_requirements'   => false,
            'live_feedback'       => false,
            'default_level'       => 'strong',
            'generator_mode'      => 'modal',
            'generator_count'     => 5,
            'use_password_toggle' => false,
            'levels'              => ['weak' => ['min_length' => 6]],
        ]], $container);

        self::assertSame('bootstrap_5_layout.html.twig', $container->getParameter('nowo_password_strength.form_theme'));
        self::assertSame('above', $container->getParameter('nowo_password_strength.feedback_position'));
        self::assertFalse($container->getParameter('nowo_password_strength.show_requirements'));
        self::assertFalse($container->getParameter('nowo_password_strength.live_feedback'));
        self::assertSame('strong', $container->getParameter('nowo_password_strength.default_level'));
        self::assertSame('modal', $container->getParameter('nowo_password_strength.generator_mode'));
        self::assertSame(5, $container->getParameter('nowo_password_strength.generator_count'));
        self::assertFalse($container->getParameter('nowo_password_strength.use_password_toggle'));
        self::assertIsString($container->getParameter('nowo_password_strength.parent_form_type'));
    }

    public function testPrependDoesNothingWithoutTwigExtension(): void
    {
        $container = new ContainerBuilder();
        (new PasswordStrengthExtension())->prepend($container);

        self::assertFalse($container->hasExtension('twig'));
    }

    public function testPrependAddsFormThemeWhenTwigIsRegistered(): void
    {
        $container = new ContainerBuilder();
        $container->registerExtension(new TwigExtension());
        $container->registerExtension(new PasswordStrengthExtension());
        $container->loadFromExtension('nowo_password_strength', [
            'form_theme' => 'form_div_layout.html.twig',
        ]);

        (new PasswordStrengthExtension())->prepend($container);

        $twigConfigs = $container->getExtensionConfig('twig');
        self::assertNotEmpty($twigConfigs);
        self::assertStringContainsString(
            'password_strength_theme.html.twig',
            (string) json_encode($twigConfigs),
        );
    }

    public function testPrependUsesBootstrapThemeMapping(): void
    {
        $container = new ContainerBuilder();
        $container->registerExtension(new TwigExtension());
        $container->registerExtension(new PasswordStrengthExtension());
        $container->loadFromExtension('nowo_password_strength', [
            'form_theme' => 'bootstrap_5_layout.html.twig',
        ]);

        (new PasswordStrengthExtension())->prepend($container);

        self::assertStringContainsString(
            'password_strength_theme_bootstrap5.html.twig',
            (string) json_encode($container->getExtensionConfig('twig')),
        );
    }

    public function testGetAlias(): void
    {
        self::assertSame('nowo_password_strength', (new PasswordStrengthExtension())->getAlias());
    }

    public function testPrependConfiguresAssets(): void
    {
        $container = new ContainerBuilder();
        $container->registerExtension(new \Symfony\Bundle\FrameworkBundle\DependencyInjection\FrameworkExtension());
        (new PasswordStrengthExtension())->prepend($container);

        self::assertSame(
            '/bundles/passwordstrength',
            $container->getExtensionConfig('framework')[0]['assets']['packages']['nowo_password_strength']['base_path'],
        );
    }
}
