<?php

declare(strict_types=1);

namespace Nowo\PasswordStrengthBundle\Tests\Unit\Profile;

use Nowo\PasswordStrengthBundle\Profile\ConfigPasswordProfileProvider;
use PHPUnit\Framework\TestCase;

final class ConfigPasswordProfileProviderTest extends TestCase
{
    public function testReturnsConfiguredProfiles(): void
    {
        $provider = new ConfigPasswordProfileProvider([
            'admin'  => ['min_length' => 16, 'require_digit' => true],
            'nested' => ['conditions' => ['min_length' => 11]],
        ]);

        $admin  = $provider->get('admin');
        $nested = $provider->get('nested');
        self::assertNotNull($admin);
        self::assertNotNull($nested);
        self::assertSame(16, $admin->minLength);
        self::assertTrue($admin->requireDigit);
        self::assertSame(11, $nested->minLength);
        self::assertNull($provider->get('missing'));
        self::assertSame(['admin', 'nested'], $provider->names());
    }

    public function testDefaultsToTheBuiltInProfiles(): void
    {
        $provider = new ConfigPasswordProfileProvider();

        self::assertSame(['weak', 'medium', 'strong'], $provider->names());
        self::assertNotNull($provider->get('strong'));
    }
}
