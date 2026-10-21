<?php

declare(strict_types=1);

namespace Nowo\PasswordStrengthBundle\Tests\Unit\DependencyInjection;

use Nowo\PasswordStrengthBundle\DependencyInjection\Configuration;
use PHPUnit\Framework\TestCase;
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
