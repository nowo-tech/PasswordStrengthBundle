<?php

declare(strict_types=1);

namespace Nowo\PasswordStrengthBundle\Tests\Unit\DependencyInjection\Compiler;

use Nowo\PasswordStrengthBundle\DependencyInjection\Compiler\TwigPathsPass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\Alias;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

final class TwigPathsPassTest extends TestCase
{
    public function testProcessAddsViewsPathWhenNativeLoaderDefinitionExists(): void
    {
        $container = new ContainerBuilder();
        $loader    = new Definition();
        $container->setDefinition('twig.loader.native_filesystem', $loader);

        (new TwigPathsPass())->process($container);

        self::assertCount(1, $loader->getMethodCalls());
        self::assertSame('addPath', $loader->getMethodCalls()[0][0]);
        self::assertSame('PasswordStrengthBundle', $loader->getMethodCalls()[0][1][1]);
    }

    public function testProcessUsesNativeLoaderAlias(): void
    {
        $container = new ContainerBuilder();
        $loader    = new Definition();
        $container->setDefinition('twig.loader.native_filesystem', $loader);
        $container->setAlias('twig.loader.native', new Alias('twig.loader.native_filesystem'));

        (new TwigPathsPass())->process($container);

        self::assertCount(1, $loader->getMethodCalls());
    }

    public function testProcessUsesNativeLoaderDefinition(): void
    {
        $container = new ContainerBuilder();
        $loader    = new Definition();
        $container->setDefinition('twig.loader.native', $loader);

        (new TwigPathsPass())->process($container);

        self::assertCount(1, $loader->getMethodCalls());
    }

    public function testProcessSkipsWhenNoLoader(): void
    {
        $container = new ContainerBuilder();

        (new TwigPathsPass())->process($container);

        self::assertFalse($container->hasDefinition('twig.loader.native_filesystem'));
    }
}
