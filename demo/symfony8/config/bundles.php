<?php

declare(strict_types=1);

return [
    Symfony\Bundle\FrameworkBundle\FrameworkBundle::class     => ['all' => true],
    Symfony\Bundle\TwigBundle\TwigBundle::class               => ['all' => true],
    Symfony\UX\Icons\UXIconsBundle::class                     => ['all' => true],
    Symfony\Bundle\DebugBundle\DebugBundle::class             => ['dev' => true],
    Symfony\Bundle\WebProfilerBundle\WebProfilerBundle::class => ['dev' => true],
    Nowo\PasswordStrengthBundle\PasswordStrengthBundle::class => ['all' => true],
    Nowo\PasswordToggleBundle\NowoPasswordToggleBundle::class => ['all' => true],
    Nowo\TwigInspectorBundle\NowoTwigInspectorBundle::class   => ['dev' => true, 'test' => true],
];
