<?php

declare(strict_types=1);

namespace Nowo\PasswordStrengthBundle\Tests\Unit\Service;

use Nowo\PasswordStrengthBundle\Model\PasswordConditions;
use Nowo\PasswordStrengthBundle\Service\PasswordPatternBuilder;
use Nowo\PasswordStrengthBundle\Service\PasswordStrengthEvaluator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class PasswordStrengthEvaluatorTest extends TestCase
{
    private PasswordStrengthEvaluator $evaluator;

    protected function setUp(): void
    {
        $this->evaluator = new PasswordStrengthEvaluator(new PasswordPatternBuilder());
    }

    #[DataProvider('passwordProvider')]
    public function testEvaluate(string $password, array $config, bool $expectedValid, array $expectedMissing): void
    {
        $conditions = PasswordConditions::fromArray($config);
        $result     = $this->evaluator->evaluate($password, $conditions);

        self::assertSame($expectedValid, $result->valid);
        foreach ($expectedMissing as $missing) {
            self::assertContains($missing, $result->missing);
        }
    }

    public static function passwordProvider(): iterable
    {
        yield 'weak too short' => ['abc', ['min_length' => 8], false, ['min_length']];
        yield 'medium valid' => ['Abcdef1!', [
            'min_length' => 8,
            'require_lowercase' => true,
            'require_uppercase' => true,
            'require_digit' => true,
        ], true, []];
        yield 'missing uppercase' => ['abcdef1!', [
            'min_length' => 8,
            'require_uppercase' => true,
        ], false, ['require_uppercase']];
    }
}
