<?php

declare(strict_types=1);

namespace Nowo\PasswordStrengthBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;

use function array_key_exists;
use function array_keys;
use function array_replace;
use function implode;
use function is_array;
use function sprintf;

/**
 * Configuration for Password Strength Bundle.
 *
 * Named condition sets follow REQ-CFG-001: `default_profile` + `profiles`. The former keys
 * `default_level` / `levels` are still accepted and mapped onto the canonical keys.
 */
final class Configuration implements ConfigurationInterface
{
    public const ALIAS = 'nowo_password_strength';

    public const DEFAULT_PROFILE = 'medium';

    /**
     * Built-in profiles, always available (a configured profile with the same name replaces one).
     *
     * @var array<string, array<string, bool|int>>
     */
    public const BUILTIN_PROFILES = [
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
    ];

    /**
     * Legacy key => canonical key (REQ-CFG-001 rule 6).
     *
     * @var array<string, string>
     */
    private const LEGACY_KEYS = [
        'default_level' => 'default_profile',
        'levels'        => 'profiles',
    ];

    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder(self::ALIAS);
        $root        = $treeBuilder->getRootNode();

        $root
            ->beforeNormalization()
                ->ifArray()
                ->then(static function (array $v): array {
                    foreach (self::LEGACY_KEYS as $legacy => $canonical) {
                        if (!array_key_exists($legacy, $v)) {
                            continue;
                        }
                        if (array_key_exists($canonical, $v)) {
                            throw new InvalidConfigurationException(sprintf('nowo_password_strength: "%s" is the legacy name of "%s"; set only "%s".', $legacy, $canonical, $canonical));
                        }
                        $v[$canonical] = $v[$legacy];
                        unset($v[$legacy]);
                    }

                    return $v;
                })
            ->end()
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
                ->scalarNode('default_profile')
                    ->info('Profile used when a field or constraint gives no explicit level (REQ-CFG-001). Must exist under profiles.')
                    ->cannotBeEmpty()
                    ->defaultValue(self::DEFAULT_PROFILE)
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
                ->scalarNode('ui_translation_domain')
                    ->defaultNull()
                    ->info('Translation domain of the widget copy (requirements, generator). null = the field translation_domain (default NowoPasswordStrengthBundle); set NowoPasswordStrengthBundle when your forms use another domain for labels (2.8).')
                ->end()
                ->scalarNode('csp_nonce_attribute')
                    ->defaultValue('csp_nonce')
                    ->info('Request attribute holding the per-request CSP nonce (nowo-tech convention: csp_nonce, set by HttpHardeningBundle). The form type stamps it on the widget (data-nonce, data-password-strength-nonce) so password-strength.js nonces the <style> it injects. Empty string disables.')
                ->end()
                ->scalarNode('parent_form_type')
                    ->defaultNull()
                    ->info('Parent form type FQCN. null = auto: Symfony PasswordType, or PasswordToggleBundle PasswordType when installed and use_password_toggle is true.')
                ->end()
                ->arrayNode('profiles')
                    ->info('Named condition profiles for policy_mode: level (REQ-CFG-001). The built-in weak, medium and strong profiles are always available; an entry with the same name overrides one. Every key is validated (REQ-SF-006).')
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
                    ->defaultValue(self::BUILTIN_PROFILES)
                ->end()
            ->end()
            ->validate()
                ->always(static function (array $v): array {
                    /** @var array<string, array<string, mixed>> $profiles */
                    $profiles      = is_array($v['profiles'] ?? null) ? $v['profiles'] : [];
                    $v['profiles'] = array_replace(self::BUILTIN_PROFILES, $profiles);

                    return $v;
                })
            ->end()
            ->validate()
                ->ifTrue(static fn (array $v): bool => !isset($v['profiles'][$v['default_profile']]))
                ->then(static function (array $v): never {
                    throw new InvalidConfigurationException(sprintf('nowo_password_strength.default_profile "%s" must exist under nowo_password_strength.profiles (available: %s).', (string) $v['default_profile'], implode(', ', array_keys($v['profiles']))));
                })
            ->end();

        return $treeBuilder;
    }
}
