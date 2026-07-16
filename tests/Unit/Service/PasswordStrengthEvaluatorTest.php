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

    /**
     * @param array<string, mixed> $config
     * @param list<string> $expectedMissing
     */
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

    /**
     * @return iterable<string, array{0: string, 1: array<string, mixed>, 2: bool, 3: list<string>}>
     */
    public static function passwordProvider(): iterable
    {
        yield 'weak too short' => ['abc', ['min_length' => 8], false, ['min_length']];
        yield 'medium valid' => ['Abcdef1!', [
            'min_length'        => 8,
            'require_lowercase' => true,
            'require_uppercase' => true,
            'require_digit'     => true,
        ], true, []];
        yield 'missing uppercase' => ['abcdef1!', [
            'min_length'        => 8,
            'require_uppercase' => true,
        ], false, ['require_uppercase']];
        yield 'max length exceeded' => ['abcdefghij', ['max_length' => 5], false, ['max_length']];
        yield 'missing digit' => ['Abcdefgh', ['require_digit' => true], false, ['require_digit']];
        yield 'missing special' => ['Abcdef12', ['require_special' => true], false, ['require_special']];
        yield 'whitespace rejected' => ['Ab cd12', ['disallow_whitespace' => true], false, ['disallow_whitespace']];
        yield 'forbidden fragment' => ['password1', ['not_contain' => ['password']], false, ['not_contain']];
        yield 'min unique chars' => ['aaaab', ['min_unique_chars' => 4], false, ['min_unique_chars']];
        yield 'custom regex fail' => ['abc', ['regex' => '^[0-9]+$'], false, ['regex']];
        yield 'custom regex pass' => ['12345', ['regex' => '^[0-9]+$'], true, []];
        yield 'null password treated as empty' => ['', ['min_length' => 8], false, ['min_length']];
    }

    public function testEvaluateWithDelimitedRegex(): void
    {
        $result = $this->evaluator->evaluate('ABC', PasswordConditions::fromArray([
            'regex' => '^[A-Z]+$',
        ]));

        self::assertTrue($result->valid);
    }

    public function testEmptyPasswordWithNoRequirementsIsValid(): void
    {
        $result = $this->evaluator->evaluate('', new PasswordConditions());

        self::assertTrue($result->valid);
        self::assertSame('.*', $result->pattern);
    }

    public function testToArrayOnEvaluationResult(): void
    {
        $result = $this->evaluator->evaluate('Ab1', PasswordConditions::fromArray(['min_length' => 8]));

        self::assertFalse($result->toArray()['valid']);
        self::assertNotEmpty($result->toArray()['requirements']);
    }

    public function testEvaluateAcceptsCustomRegex(): void
    {
        $result = $this->evaluator->evaluate('ABC', PasswordConditions::fromArray([
            'regex' => '^[A-Z]+$',
        ]));

        self::assertTrue($result->valid);
    }

    public function testEvaluateRejectsWhenCustomRegexDoesNotMatch(): void
    {
        $result = $this->evaluator->evaluate('abcdef', PasswordConditions::fromArray([
            'regex' => '^[0-9]+$',
        ]));

        self::assertFalse($result->valid);
        self::assertContains('regex', $result->missing);
    }

    public function testEvaluateAcceptsDelimitedRegexLiteralForRequirementCheck(): void
    {
        $result = $this->evaluator->evaluate('abc', PasswordConditions::fromArray([
            'regex' => '/^[A-Z]+$/i',
        ]));

        // Requirement uses wrapRegex (delimited form); HTML pattern reuses the raw string and may not match.
        self::assertSame('regex', $result->requirements[0]->id);
        self::assertTrue($result->requirements[0]->met);
    }
}
