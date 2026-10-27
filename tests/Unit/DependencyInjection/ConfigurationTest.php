<?php

declare(strict_types=1);

namespace Nowo\PasswordStrengthBundle\Tests\Unit\DependencyInjection;

use Nowo\PasswordStrengthBundle\DependencyInjection\Configuration;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Config\Definition\Processor;

use function array_keys;

final class ConfigurationTest extends TestCase
{
    public function testProcessConfigurationWithDefaults(): void
    {
        $config = $this->processConfiguration([]);

        self::assertSame('below', $config['feedback_position']);
        self::assertSame('off', $config['generator_mode']);
        self::assertSame(3, $config['generator_count']);
        self::assertTrue($config['use_password_toggle']);
        self::assertNull($config['parent_form_type']);
        self::assertSame('medium', $config['default_profile']);
        self::assertSame(Configuration::BUILTIN_PROFILES, $config['profiles']);
        self::assertArrayNotHasKey('levels', $config);
        self::assertArrayNotHasKey('default_level', $config);
        self::assertSame('csp_nonce', $config['csp_nonce_attribute']);
    }

    public function testCspNonceAttributeCanBeRenamedOrDisabled(): void
    {
        self::assertSame('app_nonce', $this->processConfiguration(['csp_nonce_attribute' => 'app_nonce'])['csp_nonce_attribute']);
        self::assertSame('', $this->processConfiguration(['csp_nonce_attribute' => ''])['csp_nonce_attribute']);
    }

    public function testProfileKeysAreValidated(): void
    {
        $config = $this->processConfiguration(['profiles' => [
            'custom' => [
                'min_length'       => 10,
                'require_digit'    => true,
                'not_contain'      => ['password'],
                'regex'            => '^[A-Za-z0-9!]+$',
                'min_unique_chars' => 5,
                'uppercase'        => true,
            ],
        ]]);

        self::assertSame(['min_length' => 10, 'require_digit' => true, 'not_contain' => ['password'], 'regex' => '^[A-Za-z0-9!]+$', 'min_unique_chars' => 5, 'uppercase' => true], $config['profiles']['custom']);
        // Built-in profiles stay available next to custom ones.
        self::assertSame(Configuration::BUILTIN_PROFILES['weak'], $config['profiles']['weak']);
    }

    public function testTwoProfilesWithDefaultPointingAtOneOfThem(): void
    {
        $config = $this->processConfiguration([
            'default_profile' => 'admin',
            'profiles'        => [
                'admin'  => ['min_length' => 16, 'require_special' => true],
                'kiosk'  => ['min_length' => 4],
                'medium' => ['min_length' => 9],
            ],
        ]);

        self::assertSame('admin', $config['default_profile']);
        self::assertSame(['min_length' => 16, 'require_special' => true, 'not_contain' => []], $config['profiles']['admin']);
        self::assertSame(['min_length' => 4, 'not_contain' => []], $config['profiles']['kiosk']);
        // A configured profile named like a built-in one is merged onto it: only the keys it sets change.
        self::assertSame(['min_length' => 9, 'require_lowercase' => true, 'require_uppercase' => true, 'require_digit' => true, 'not_contain' => []], $config['profiles']['medium']);
        self::assertSame(['weak', 'medium', 'strong', 'admin', 'kiosk'], array_keys($config['profiles']));
    }

    public function testDefaultProfileMayPointAtABuiltInProfile(): void
    {
        $config = $this->processConfiguration(['default_profile' => 'strong', 'profiles' => ['custom' => ['min_length' => 10]]]);

        self::assertSame('strong', $config['default_profile']);
    }

    public function testUnknownDefaultProfileIsRejected(): void
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage('default_profile "missing" must exist under nowo_password_strength.profiles (available: weak, medium, strong, custom)');
        $this->processConfiguration(['default_profile' => 'missing', 'profiles' => ['custom' => ['min_length' => 10]]]);
    }

    public function testEmptyDefaultProfileIsRejected(): void
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->processConfiguration(['default_profile' => '']);
    }

    public function testLegacyLevelKeysAreNormalizedOntoProfiles(): void
    {
        $config = $this->processConfiguration([
            'default_level' => 'custom',
            'levels'        => ['custom' => ['min_length' => 10]],
        ]);

        self::assertSame('custom', $config['default_profile']);
        self::assertSame(['min_length' => 10, 'not_contain' => []], $config['profiles']['custom']);
        self::assertArrayNotHasKey('levels', $config);
        self::assertArrayNotHasKey('default_level', $config);
    }

    public function testLegacyAndCanonicalKeysAcrossFilesAreMerged(): void
    {
        $config = (new Processor())->processConfiguration(new Configuration(), [
            ['levels' => ['custom' => ['min_length' => 10]]],
            ['default_profile' => 'custom', 'profiles' => ['admin' => ['min_length' => 16]]],
        ]);

        self::assertSame('custom', $config['default_profile']);
        self::assertSame(['min_length' => 10, 'not_contain' => []], $config['profiles']['custom']);
        self::assertSame(['min_length' => 16, 'not_contain' => []], $config['profiles']['admin']);
    }

    public function testUnknownLegacyDefaultLevelIsRejected(): void
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage('default_profile "missing" must exist');
        $this->processConfiguration(['default_level' => 'missing']);
    }

    public function testLegacyAndCanonicalKeyInTheSameFileAreRejected(): void
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage('"levels" is the legacy name of "profiles"; set only "profiles"');
        $this->processConfiguration(['levels' => ['a' => ['min_length' => 1]], 'profiles' => ['b' => ['min_length' => 2]]]);
    }

    public function testBuiltInRuleCanBeTurnedOffExplicitly(): void
    {
        $config = $this->processConfiguration(['profiles' => ['medium' => ['require_uppercase' => false]]]);

        self::assertFalse($config['profiles']['medium']['require_uppercase']);
        self::assertSame(8, $config['profiles']['medium']['min_length']);
    }

    /**
     * @return iterable<string, array{array<string, mixed>}>
     */
    public static function ruleLessProfileProvider(): iterable
    {
        yield 'empty' => [[]];
        yield 'only special_chars' => [['special_chars' => '!?']];
        yield 'only regex_message' => [['regex_message' => 'nope']];
        yield 'all rules off' => [['min_length' => 0, 'require_digit' => false, 'not_contain' => []]];
    }

    /**
     * @param array<string, mixed> $profile
     */
    #[DataProvider('ruleLessProfileProvider')]
    public function testProfileWithoutRulesIsRejected(array $profile): void
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage('nowo_password_strength.profiles.lax holds no rule');
        $this->processConfiguration(['profiles' => ['lax' => $profile]]);
    }

    public function testBuiltInProfileWithEveryRuleTurnedOffIsRejected(): void
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage('nowo_password_strength.profiles.weak holds no rule');
        $this->processConfiguration(['profiles' => ['weak' => ['min_length' => 0]]]);
    }

    public function testUnknownProfileKeyIsRejected(): void
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->processConfiguration(['profiles' => ['custom' => ['min_lenght' => 10]]]);
    }

    public function testNegativeMinLengthIsRejected(): void
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->processConfiguration(['profiles' => ['custom' => ['min_length' => -1]]]);
    }

    /**
     * @param array<string, mixed> $config
     *
     * @return array<string, mixed>
     */
    private function processConfiguration(array $config): array
    {
        $processor = new Processor();

        return $processor->processConfiguration(new Configuration(), [$config]);
    }
}
