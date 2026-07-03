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
                ->scalarNode('parent_form_type')
                    ->defaultNull()
                    ->info('Parent form type FQCN. null = auto: Symfony PasswordType, or PasswordToggleBundle PasswordType when installed and use_password_toggle is true.')
                ->end()
                ->arrayNode('levels')
                    ->useAttributeAsKey('name')
                    ->variablePrototype()->end()
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
