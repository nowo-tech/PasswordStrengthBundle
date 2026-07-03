<?php

declare(strict_types=1);

namespace Nowo\PasswordStrengthBundle\Tests\Unit\Service;

use Nowo\PasswordStrengthBundle\Model\PasswordConditions;
use Nowo\PasswordStrengthBundle\Service\PasswordPatternBuilder;
use PHPUnit\Framework\TestCase;

final class PasswordPatternBuilderTest extends TestCase
{
    private PasswordPatternBuilder $builder;

    protected function setUp(): void
    {
        $this->builder = new PasswordPatternBuilder();
    }

    public function testBuildsLookaheadPattern(): void
    {
        $pattern = $this->builder->build(PasswordConditions::fromArray([
            'min_length'        => 8,
            'require_lowercase' => true,
            'require_uppercase' => true,
            'require_digit'     => true,
        ]));

        self::assertStringContainsString('(?=.*[a-z])', $pattern);
        self::assertStringContainsString('{8,}', $pattern);
        self::assertMatchesRegularExpression('/' . $pattern . '/', 'Abcdef12');
    }

    public function testReturnsWildcardWhenNoRules(): void
    {
        self::assertSame('.*', $this->builder->build(new PasswordConditions()));
    }

    public function testMaxLengthQuantifier(): void
    {
        $pattern = $this->builder->build(PasswordConditions::fromArray([
            'min_length' => 4,
            'max_length' => 12,
        ]));

        self::assertStringContainsString('{4,12}', $pattern);
    }

    public function testDisallowWhitespaceAndNotContain(): void
    {
        $pattern = $this->builder->build(PasswordConditions::fromArray([
            'min_length'          => 6,
            'disallow_whitespace' => true,
            'not_contain'         => ['admin'],
            'require_special'     => true,
            'special_chars'       => '!',
        ]));

        self::assertStringContainsString('(?!.*\\s)', $pattern);
        self::assertStringContainsString('(?!.*admin)', $pattern);
        self::assertStringContainsString('\\S', $pattern);
    }

    public function testCustomRegexOverridesLookaheads(): void
    {
        self::assertSame(
            '^[A-Z]{3}$',
            $this->builder->build(PasswordConditions::fromArray([
                'regex'             => '^[A-Z]{3}$',
                'require_lowercase' => true,
            ])),
        );
    }

    public function testMinUniqueCharsLookahead(): void
    {
        $pattern = $this->builder->build(PasswordConditions::fromArray([
            'min_unique_chars' => 4,
            'min_length'       => 4,
        ]));

        self::assertStringContainsString('(?=(?:.*(.)((?!\\1).)){0,}3)', $pattern);
    }
}
