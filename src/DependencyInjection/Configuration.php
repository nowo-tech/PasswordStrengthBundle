<?php

declare(strict_types=1);

namespace Nowo\PasswordStrengthBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * Configuration for Password Strength Bundle.
 */
final class Configuration implements ConfigurationInterface
{
    public const ALIAS = 'nowo_password_strength';

    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder(self::ALIAS);
        $root        = $treeBuilder->getRootNode();

        $root
            ->children()
                ->scalarNode('form_theme')
                    ->info('Base Symfony form layout (must match twig.form_themes in the app).')
                    ->defaultValue('form_div_layout.html.twig')
                ->end()
                ->enumNode('feedback_position')
                    ->values(['above', 'below'])
                    ->defaultValue('below')
                ->end()
                ->booleanNode('show_requirements')
                    ->defaultTrue()
                ->end()
                ->booleanNode('live_feedback')
                    ->defaultTrue()
                ->end()
                ->scalarNode('default_level')
                    ->defaultValue('medium')
                ->end()
                ->enumNode('generator_mode')
                    ->values(['off', 'input', 'modal'])
                    ->defaultValue('off')
                    ->info('Default password generator: off, fill input (visible), or modal with copy.')
                ->end()
                ->integerNode('generator_count')
                    ->min(1)
                    ->max(10)
                    ->defaultValue(3)
                    ->info('Number of suggestions in modal mode.')
                ->end()
                ->booleanNode('use_password_toggle')
                    ->defaultTrue()
                    ->info('When true, use PasswordToggleBundle as parent if installed; ignored when parent_form_type is set explicitly.')
                ->end()
                ->scalarNode('csp_nonce_attribute')
                    ->defaultValue('csp_nonce')
                    ->info('Request attribute holding the per-request CSP nonce (nowo-tech convention: csp_nonce, set by HttpHardeningBundle). The form type stamps it on the widget (data-nonce, data-password-strength-nonce) so password-strength.js nonces the <style> it injects. Empty string disables.')
                ->end()
                ->scalarNode('parent_form_type')
                    ->defaultNull()
                    ->info('Parent form type FQCN. null = auto: Symfony PasswordType, or PasswordToggleBundle PasswordType when installed and use_password_toggle is true.')
                ->end()
                ->arrayNode('levels')
                    ->info('Named condition presets for policy_mode: level. Every key is validated (REQ-SF-006).')
                    ->useAttributeAsKey('name')
                    ->arrayPrototype()
                        ->children()
                            ->integerNode('min_length')->min(0)->end()
                            ->integerNode('max_length')->min(0)->end()
                            ->booleanNode('require_lowercase')->end()
                            ->booleanNode('require_uppercase')->end()
                            ->booleanNode('require_digit')->end()
                            ->booleanNode('require_special')->end()
                            ->scalarNode('special_chars')->end()
                            ->booleanNode('disallow_whitespace')->end()
                            ->arrayNode('not_contain')->scalarPrototype()->end()->end()
                            ->integerNode('min_unique_chars')->min(0)->end()
                            ->scalarNode('regex')->end()
                            ->scalarNode('regex_message')->end()
                            // Short aliases accepted by PasswordConditions::fromArray().
                            ->booleanNode('lowercase')->end()
                            ->booleanNode('uppercase')->end()
                            ->booleanNode('digit')->end()
                            ->booleanNode('special')->end()
                            ->booleanNode('no_whitespace')->end()
                        ->end()
                    ->end()
                    ->defaultValue([
                        'weak'   => ['min_length' => 6],
                        'medium' => [
                            'min_length'        => 8,
                            'require_lowercase' => true,
                            'require_uppercase' => true,
                            'require_digit'     => true,
                        ],
                        'strong' => [
                            'min_length'        => 12,
                            'require_lowercase' => true,
                            'require_uppercase' => true,
                            'require_digit'     => true,
                            'require_special'   => true,
                        ],
                    ])
                ->end()
            ->end();

        return $treeBuilder;
    }
}
