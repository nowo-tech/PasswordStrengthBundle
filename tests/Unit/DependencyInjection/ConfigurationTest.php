<?php

declare(strict_types=1);

namespace Nowo\PasswordStrengthBundle\Tests\Unit\DependencyInjection;

use Nowo\PasswordStrengthBundle\DependencyInjection\Configuration;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Config\Definition\Processor;

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
        self::assertArrayHasKey('medium', $config['levels']);
        self::assertSame('csp_nonce', $config['csp_nonce_attribute']);
    }

    public function testCspNonceAttributeCanBeRenamedOrDisabled(): void
    {
        self::assertSame('app_nonce', $this->processConfiguration(['csp_nonce_attribute' => 'app_nonce'])['csp_nonce_attribute']);
        self::assertSame('', $this->processConfiguration(['csp_nonce_attribute' => ''])['csp_nonce_attribute']);
    }

    public function testLevelKeysAreValidated(): void
    {
        $config = $this->processConfiguration(['levels' => [
            'custom' => [
                'min_length'       => 10,
                'require_digit'    => true,
                'not_contain'      => ['password'],
                'regex'            => '^[A-Za-z0-9!]+$',
                'min_unique_chars' => 5,
                'uppercase'        => true,
            ],
        ]]);

        self::assertSame(['min_length' => 10, 'require_digit' => true, 'not_contain' => ['password'], 'regex' => '^[A-Za-z0-9!]+$', 'min_unique_chars' => 5, 'uppercase' => true], $config['levels']['custom']);
        self::assertArrayNotHasKey('weak', $config['levels']);
    }

    public function testUnknownLevelKeyIsRejected(): void
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->processConfiguration(['levels' => ['custom' => ['min_lenght' => 10]]]);
    }

    public function testNegativeMinLengthIsRejected(): void
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->processConfiguration(['levels' => ['custom' => ['min_length' => -1]]]);
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
