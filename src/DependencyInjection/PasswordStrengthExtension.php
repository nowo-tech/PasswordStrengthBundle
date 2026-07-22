<?php

declare(strict_types=1);

namespace Nowo\PasswordStrengthBundle\DependencyInjection;

use Nowo\PasswordStrengthBundle\Integration\ParentFormTypeResolver;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

/**
 * Loads bundle configuration, services, and prepends the form theme.
 */
final class PasswordStrengthExtension extends Extension implements PrependExtensionInterface
{
    /** @var array<string, string> */
    private const FORM_THEME_MAP = [
        'form_div_layout.html.twig'               => '@NowoPasswordStrengthBundle/Form/password_strength_theme.html.twig',
        'form_table_layout.html.twig'             => '@NowoPasswordStrengthBundle/Form/password_strength_theme_table.html.twig',
        'bootstrap_5_layout.html.twig'            => '@NowoPasswordStrengthBundle/Form/password_strength_theme_bootstrap5.html.twig',
        'bootstrap_5_horizontal_layout.html.twig' => '@NowoPasswordStrengthBundle/Form/password_strength_theme_bootstrap5_horizontal.html.twig',
        'bootstrap_4_layout.html.twig'            => '@NowoPasswordStrengthBundle/Form/password_strength_theme_bootstrap4.html.twig',
        'bootstrap_4_horizontal_layout.html.twig' => '@NowoPasswordStrengthBundle/Form/password_strength_theme_bootstrap4_horizontal.html.twig',
        'bootstrap_3_layout.html.twig'            => '@NowoPasswordStrengthBundle/Form/password_strength_theme_bootstrap3.html.twig',
        'bootstrap_3_horizontal_layout.html.twig' => '@NowoPasswordStrengthBundle/Form/password_strength_theme_bootstrap3_horizontal.html.twig',
        'foundation_5_layout.html.twig'           => '@NowoPasswordStrengthBundle/Form/password_strength_theme_foundation5.html.twig',
        'foundation_6_layout.html.twig'           => '@NowoPasswordStrengthBundle/Form/password_strength_theme_foundation6.html.twig',
        'tailwind_2_layout.html.twig'             => '@NowoPasswordStrengthBundle/Form/password_strength_theme_tailwind2.html.twig',
    ];

    public function load(array $configs, ContainerBuilder $container): void
    {
        $loader = new YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.yaml');

        $config = $this->processConfiguration(new Configuration(), $configs);

        $container->setParameter(Configuration::ALIAS . '.levels', $config['levels'] ?? []);
        $container->setParameter(Configuration::ALIAS . '.form_theme', $config['form_theme'] ?? 'form_div_layout.html.twig');
        $container->setParameter(Configuration::ALIAS . '.feedback_position', $config['feedback_position'] ?? 'below');
        $container->setParameter(Configuration::ALIAS . '.show_requirements', $config['show_requirements'] ?? true);
        $container->setParameter(Configuration::ALIAS . '.live_feedback', $config['live_feedback'] ?? true);
        $container->setParameter(Configuration::ALIAS . '.default_level', $config['default_level'] ?? 'medium');
        $container->setParameter(Configuration::ALIAS . '.generator_mode', $config['generator_mode'] ?? 'off');
        $container->setParameter(Configuration::ALIAS . '.generator_count', $config['generator_count'] ?? 3);
        $container->setParameter(Configuration::ALIAS . '.use_password_toggle', $config['use_password_toggle'] ?? true);

        $resolvedParent = ParentFormTypeResolver::resolve(
            $config['parent_form_type'] ?? null,
            (bool) ($config['use_password_toggle'] ?? true),
        );
        $container->setParameter(Configuration::ALIAS . '.parent_form_type', $resolvedParent);
    }

    public function prepend(ContainerBuilder $container): void
    {
        if ($container->hasExtension('framework')) {
            $container->prependExtensionConfig('framework', [
                'assets' => [
                    'packages' => [
                        Configuration::ALIAS => [
                            'base_path' => '/bundles/passwordstrength',
                        ],
                    ],
                ],
            ]);
        }

        if (!$container->hasExtension('twig')) {
            return;
        }

        $configs        = $container->getExtensionConfig(Configuration::ALIAS);
        $config         = $this->processConfiguration(new Configuration(), $configs);
        $formTheme      = $config['form_theme'] ?? 'form_div_layout.html.twig';
        $themePath      = self::FORM_THEME_MAP[$formTheme] ?? self::FORM_THEME_MAP['form_div_layout.html.twig'];
        $resolvedParent = ParentFormTypeResolver::resolve(
            $config['parent_form_type'] ?? null,
            (bool) ($config['use_password_toggle'] ?? true),
        );
        $prependToggleTheme = ParentFormTypeResolver::shouldPrependToggleTheme(
            $resolvedParent,
            (bool) ($config['use_password_toggle'] ?? true),
        );

        $container->prependExtensionConfig('twig', [
            'form_themes' => array_values(array_filter([
                $prependToggleTheme ? '@NowoPasswordToggleBundle/Form/toggle_password_widget.html.twig' : null,
                $themePath,
            ])),
        ]);
    }

    public function getAlias(): string
    {
        return Configuration::ALIAS;
    }
}
