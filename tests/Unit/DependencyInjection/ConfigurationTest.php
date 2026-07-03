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
