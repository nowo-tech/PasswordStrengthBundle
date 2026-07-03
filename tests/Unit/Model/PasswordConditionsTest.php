<?php

declare(strict_types=1);

namespace Nowo\PasswordStrengthBundle\Tests\Unit\Model;

use Nowo\PasswordStrengthBundle\Model\PasswordConditions;
use PHPUnit\Framework\TestCase;

final class PasswordConditionsTest extends TestCase
{
    public function testFromArrayWithAliasesAndDefaults(): void
    {
        $conditions = PasswordConditions::fromArray([
            'min_length'       => 10,
            'max_length'       => 20,
            'lowercase'        => true,
            'uppercase'        => true,
            'digit'            => true,
            'special'          => true,
            'special_chars'    => '@#',
            'no_whitespace'    => true,
            'not_contain'      => ['password', '', 123],
            'regex'            => '^[A-Z]+$',
            'regex_message'    => 'Uppercase only',
            'min_unique_chars' => 5,
        ]);

        self::assertSame(10, $conditions->minLength);
        self::assertSame(20, $conditions->maxLength);
        self::assertTrue($conditions->requireLowercase);
        self::assertTrue($conditions->requireUppercase);
        self::assertTrue($conditions->requireDigit);
        self::assertTrue($conditions->requireSpecial);
        self::assertSame('@#', $conditions->specialChars);
        self::assertTrue($conditions->disallowWhitespace);
        self::assertSame(['password'], $conditions->notContain);
        self::assertSame('^[A-Z]+$', $conditions->regex);
        self::assertSame('Uppercase only', $conditions->regexMessage);
        self::assertSame(5, $conditions->minUniqueChars);
    }

    public function testToArrayRoundTrip(): void
    {
        $original = PasswordConditions::fromArray(['min_length' => 8, 'require_digit' => true]);
        self::assertSame($original->toArray(), PasswordConditions::fromArray($original->toArray())->toArray());
    }
}
