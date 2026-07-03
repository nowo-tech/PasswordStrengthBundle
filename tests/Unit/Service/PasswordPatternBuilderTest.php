<?php

declare(strict_types=1);

namespace Nowo\PasswordStrengthBundle\Tests\Unit\Service;

use Nowo\PasswordStrengthBundle\Model\PasswordConditions;
use Nowo\PasswordStrengthBundle\Service\PasswordPatternBuilder;
use PHPUnit\Framework\TestCase;

final class PasswordPatternBuilderTest extends TestCase
{
    public function testBuildsLookaheadPattern(): void
    {
        $builder = new PasswordPatternBuilder();
        $pattern = $builder->build(PasswordConditions::fromArray([
            'min_length' => 8,
            'require_lowercase' => true,
            'require_uppercase' => true,
            'require_digit' => true,
        ]));

        self::assertStringContainsString('(?=.*[a-z])', $pattern);
        self::assertStringContainsString('{8,}', $pattern);
        self::assertMatchesRegularExpression('/' . $pattern . '/', 'Abcdef12');
    }
}
