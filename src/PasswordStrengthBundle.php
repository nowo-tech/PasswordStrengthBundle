<?php

declare(strict_types=1);

namespace Nowo\PasswordStrengthBundle;

use Nowo\PasswordStrengthBundle\DependencyInjection\Compiler\TwigPathsPass;
use Nowo\PasswordStrengthBundle\DependencyInjection\PasswordStrengthExtension;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * Password strength form type and validator bundle.
 *
 * Extends Symfony PasswordType with configurable strength policies (levels or conditions),
 * live client-side feedback, and server-side validation.
 */
final class PasswordStrengthBundle extends Bundle
{
    public function build(ContainerBuilder $container): void
    {
        $container->addCompilerPass(new TwigPathsPass());
    }

    public function getContainerExtension(): ExtensionInterface
    {
        if ($this->extension === null || $this->extension === false) {
            $this->extension = new PasswordStrengthExtension();
        }

        return $this->extension;
    }
}
