<?php

declare(strict_types=1);

namespace Nowo\PasswordStrengthBundle\Tests\Unit\Service;

use Nowo\PasswordStrengthBundle\Model\PasswordConditions;
use Nowo\PasswordStrengthBundle\Service\PasswordPatternBuilder;
use Nowo\PasswordStrengthBundle\Service\PasswordStrengthEvaluator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

use function file_get_contents;
use function json_decode;
use function str_repeat;

use const JSON_THROW_ON_ERROR;

/**
 * Runs tests/Fixtures/password-vectors.json, the vectors password-strength-lib.shared.test.ts runs on the TypeScript
 * side, so the PHP and browser policies cannot drift apart.
 */
final class SharedVectorsTest extends TestCase
{
    /**
     * @return array<string, mixed>
     */
    private static function fixture(): array
    {
        /** @var array<string, mixed> $data */
        $data = json_decode((string) file_get_contents(__DIR__ . '/../../Fixtures/password-vectors.json'), true, 512, JSON_THROW_ON_ERROR);

        return $data;
    }

    /**
     * @return iterable<string, array{string, string, string}>
     */
    public static function escapeProvider(): iterable
    {
        /** @var list<array{input: string, class: string, literal: string}> $escape */
        $escape = self::fixture()['escape'];
        foreach ($escape as $i => $row) {
            yield 'escape #' . $i => [$row['input'], $row['class'], $row['literal']];
        }
    }

    #[DataProvider('escapeProvider')]
    public function testEscapingMatchesTypeScript(string $input, string $class, string $literal): void
    {
        self::assertSame($class, PasswordPatternBuilder::escapeForCharClass($input));
        self::assertSame($literal, PasswordPatternBuilder::escapeLiteral($input));
        // Both forms compile in PCRE and match the input literally.
        self::assertSame(1, preg_match('/^' . $literal . '$/u', $input));
        self::assertSame(1, preg_match('/^[' . $class . ']+$/u', $input));
    }

    /**
     * @return iterable<string, array{array<string, mixed>, string, string, bool, list<string>}>
     */
    public static function vectorProvider(): iterable
    {
        /** @var list<array{name: string, conditions: array<string, mixed>, pattern: string, cases: list<array<string, mixed>>}> $vectors */
        $vectors = self::fixture()['vectors'];
        foreach ($vectors as $vector) {
            foreach ($vector['cases'] as $i => $case) {
                $password = isset($case['repeat']) ? str_repeat((string) $case['repeat'], (int) $case['times']) : (string) $case['password'];
                /** @var list<string> $missing */
                $missing = $case['missing'];

                yield $vector['name'] . ' #' . $i => [$vector['conditions'], $vector['pattern'], $password, (bool) $case['valid'], $missing];
            }
        }
    }

    /**
     * @param array<string, mixed> $conditions
     * @param list<string> $missing
     */
    #[DataProvider('vectorProvider')]
    public function testVector(array $conditions, string $pattern, string $password, bool $valid, array $missing): void
    {
        $policy    = PasswordConditions::fromArray($conditions);
        $evaluator = new PasswordStrengthEvaluator(new PasswordPatternBuilder());

        self::assertSame($pattern, (new PasswordPatternBuilder())->build($policy));

        $result = $evaluator->evaluate($password, $policy);
        self::assertSame($valid, $result->valid);
        self::assertSame($missing, $result->missing);
    }
}
