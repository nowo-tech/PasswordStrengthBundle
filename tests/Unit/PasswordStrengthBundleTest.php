<?php

declare(strict_types=1);

namespace Nowo\PasswordStrengthBundle\Tests\Unit;

use Nowo\PasswordStrengthBundle\DependencyInjection\Compiler\TwigPathsPass;
use Nowo\PasswordStrengthBundle\DependencyInjection\PasswordStrengthExtension;
use Nowo\PasswordStrengthBundle\PasswordStrengthBundle;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;

final class PasswordStrengthBundleTest extends TestCase
{
    public function testGetContainerExtension(): void
    {
        $bundle = new PasswordStrengthBundle();

        self::assertInstanceOf(PasswordStrengthExtension::class, $bundle->getContainerExtension());
        self::assertSame($bundle->getContainerExtension(), $bundle->getContainerExtension());
    }

    public function testBuildRegistersTwigPathsPass(): void
    {
        $container = new ContainerBuilder();
        (new PasswordStrengthBundle())->build($container);

        $passes = $container->getCompilerPassConfig()->getPasses();
        $found  = false;
        foreach ($passes as $pass) {
            if ($pass instanceof TwigPathsPass) {
                $found = true;
                break;
            }
        }
        self::assertTrue($found);
    }
}
