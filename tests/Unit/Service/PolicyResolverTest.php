<?php

declare(strict_types=1);

namespace Nowo\PasswordStrengthBundle\Tests\Unit\Service;

use InvalidArgumentException;
use Nowo\PasswordStrengthBundle\Model\PolicyMode;
use Nowo\PasswordStrengthBundle\Service\PolicyResolver;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class PolicyResolverTest extends TestCase
{
    public function testResolvesLevelFromConfiguration(): void
    {
        $resolver = new PolicyResolver([
            'strong' => ['min_length' => 12, 'require_special' => true],
        ]);

        $conditions = $resolver->resolve(PolicyMode::Level, ['level' => 'strong']);

        self::assertSame(12, $conditions->minLength);
        self::assertTrue($conditions->requireSpecial);
    }

    public function testResolvesInlineConditions(): void
    {
        $resolver   = new PolicyResolver();
        $conditions = $resolver->resolve(PolicyMode::Conditions, [
            'conditions' => ['min_length' => 10, 'require_digit' => true],
        ]);

        self::assertSame(10, $conditions->minLength);
        self::assertTrue($conditions->requireDigit);
    }

    public function testUsesConfiguredDefaultLevelWhenNoneGiven(): void
    {
        $resolver = new PolicyResolver([
            'medium' => ['min_length' => 8],
            'strong' => ['min_length' => 12],
        ], 'strong');

        self::assertSame(12, $resolver->resolve(PolicyMode::Level, [])->minLength);
        self::assertSame(12, $resolver->resolve(PolicyMode::Level, ['level' => null])->minLength);
        self::assertSame(8, $resolver->resolve(PolicyMode::Level, ['level' => 'medium'])->minLength);
    }

    public function testUnknownProfileThrowsInsteadOfFallingBack(): void
    {
        $resolver = new PolicyResolver(['medium' => ['min_length' => 8]]);

        self::assertTrue($resolver->hasProfile('medium'));
        self::assertFalse($resolver->hasProfile('strnog'));
        self::assertSame(['medium'], $resolver->profileNames());

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown password strength profile "strnog" (available: medium).');
        $resolver->resolve(PolicyMode::Level, ['level' => 'strnog']);
    }

    public function testUnknownDefaultProfileThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        (new PolicyResolver(['medium' => ['min_length' => 8]], 'missing'))->resolve(PolicyMode::Level, []);
    }

    public function testDefaultsToTheBuiltInProfiles(): void
    {
        $resolver = new PolicyResolver();

        self::assertSame(['weak', 'medium', 'strong'], $resolver->profileNames());
        self::assertSame(8, $resolver->resolve(PolicyMode::Level, [])->minLength);
        self::assertTrue($resolver->resolve(PolicyMode::Level, ['level' => 'strong'])->requireSpecial);
    }

    public function testResolvesNestedConditionsKey(): void
    {
        $resolver = new PolicyResolver(['custom' => ['conditions' => ['min_length' => 11]]], 'custom');

        self::assertSame(11, $resolver->resolve(PolicyMode::Level, [])->minLength);
    }

    /**
     * @return iterable<string, array{mixed, string}>
     */
    public static function invalidInlineConditionsProvider(): iterable
    {
        yield 'missing' => [null, 'requires a non-empty "conditions" array'];
        yield 'empty' => [[], 'hold no rule'];
        yield 'only cosmetic keys' => [['special_chars' => '!?', 'regex_message' => 'x'], 'hold no rule'];
        yield 'disabled rules' => [['min_length' => 0, 'require_digit' => false], 'hold no rule'];
        yield 'typo' => [['min_lenght' => 12], 'Unknown password condition key(s) "min_lenght"'];
    }

    #[DataProvider('invalidInlineConditionsProvider')]
    public function testInvalidInlineConditionsThrow(mixed $conditions, string $message): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage($message);
        (new PolicyResolver())->resolve(PolicyMode::Conditions, ['conditions' => $conditions]);
    }
}
