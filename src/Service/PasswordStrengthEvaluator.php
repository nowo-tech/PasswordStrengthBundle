<?php

declare(strict_types=1);

namespace Nowo\PasswordStrengthBundle\Service;

use Nowo\PasswordStrengthBundle\Model\EvaluationResult;
use Nowo\PasswordStrengthBundle\Model\PasswordConditions;
use Nowo\PasswordStrengthBundle\Model\RequirementResult;

use function count;
use function mb_str_split;
use function mb_strlen;
use function md5;
use function preg_match;
use function str_contains;
use function strlen;

/**
 * Evaluates a password against configured conditions (server-side mirror of the TypeScript evaluator).
 */
final readonly class PasswordStrengthEvaluator
{
    /**
     * Hard cap on the evaluated input, in bytes: longer values are rejected as a `max_length` violation before any
     * regex runs (bounds the work a single request can cause; mirrored in password-strength-lib.ts).
     */
    public const MAX_BYTES = 4096;

    public function __construct(
        private PasswordPatternBuilder $patternBuilder,
    ) {
    }

    public function evaluate(?string $password, PasswordConditions $conditions): EvaluationResult
    {
        $value        = $password ?? '';
        $requirements = [];
        $missing      = [];

        if (strlen($value) > self::MAX_BYTES) {
            return new EvaluationResult(
                false,
                [new RequirementResult('max_length', 'requirement.max_length', false, strlen($value), self::MAX_BYTES)],
                ['max_length'],
                $this->patternBuilder->build($conditions),
            );
        }

        if ($conditions->minLength > 0) {
            $length         = mb_strlen($value);
            $met            = $length >= $conditions->minLength;
            $requirements[] = new RequirementResult('min_length', 'requirement.min_length', $met, $length, $conditions->minLength);
            if (!$met) {
                $missing[] = 'min_length';
            }
        }

        if ($conditions->maxLength !== null && $conditions->maxLength > 0) {
            $length         = mb_strlen($value);
            $met            = $length <= $conditions->maxLength;
            $requirements[] = new RequirementResult('max_length', 'requirement.max_length', $met, $length, $conditions->maxLength);
            if (!$met) {
                $missing[] = 'max_length';
            }
        }

        if ($conditions->requireLowercase) {
            $met            = preg_match('/[a-z]/u', $value) === 1;
            $requirements[] = new RequirementResult('require_lowercase', 'requirement.lowercase', $met);
            if (!$met) {
                $missing[] = 'require_lowercase';
            }
        }

        if ($conditions->requireUppercase) {
            $met            = preg_match('/[A-Z]/u', $value) === 1;
            $requirements[] = new RequirementResult('require_uppercase', 'requirement.uppercase', $met);
            if (!$met) {
                $missing[] = 'require_uppercase';
            }
        }

        if ($conditions->requireDigit) {
            $met            = preg_match('/\d/u', $value) === 1;
            $requirements[] = new RequirementResult('require_digit', 'requirement.digit', $met);
            if (!$met) {
                $missing[] = 'require_digit';
            }
        }

        if ($conditions->requireSpecial) {
            $met            = preg_match('/[' . PasswordPatternBuilder::escapeForCharClass($conditions->specialChars) . ']/u', $value) === 1;
            $requirements[] = new RequirementResult('require_special', 'requirement.special', $met);
            if (!$met) {
                $missing[] = 'require_special';
            }
        }

        if ($conditions->disallowWhitespace) {
            $met            = $value === '' || preg_match('/^\S+$/u', $value) === 1;
            $requirements[] = new RequirementResult('disallow_whitespace', 'requirement.no_whitespace', $met);
            if (!$met) {
                $missing[] = 'disallow_whitespace';
            }
        }

        foreach ($conditions->notContain as $fragment) {
            $met            = $value === '' || !str_contains($value, $fragment);
            $requirements[] = new RequirementResult(
                'not_contain_' . md5($fragment),
                'requirement.not_contain',
                $met,
            );
            if (!$met) {
                $missing[] = 'not_contain';
            }
        }

        if ($conditions->minUniqueChars > 0) {
            $unique         = $this->countUniqueChars($value, $conditions->minUniqueChars);
            $met            = $unique >= $conditions->minUniqueChars;
            $requirements[] = new RequirementResult(
                'min_unique_chars',
                'requirement.min_unique_chars',
                $met,
                $unique,
                $conditions->minUniqueChars,
            );
            if (!$met) {
                $missing[] = 'min_unique_chars';
            }
        }

        if ($conditions->regex !== null) {
            $met            = $value !== '' && preg_match(PasswordPatternBuilder::toPcre($conditions->regex), $value) === 1;
            $requirements[] = new RequirementResult('regex', 'requirement.regex', $met);
            if (!$met) {
                $missing[] = 'regex';
            }
        }

        $pattern = $this->patternBuilder->build($conditions);
        $valid   = $missing === [] && ($value !== '' || $requirements === []);

        // A custom regex is already checked above exactly as configured; re-checking its HTML form would
        // double-wrap delimited patterns (`/…/i`) and reject every password.
        if ($value !== '' && $conditions->regex === null && $pattern !== '.*' && $pattern !== '') {
            $patternValid = @preg_match('/' . $pattern . '/u', $value) === 1;
            if (!$patternValid && $missing === []) {
                $valid     = false;
                $missing[] = 'pattern';
            }
        }

        return new EvaluationResult($valid, $requirements, $missing, $pattern);
    }

    /**
     * Counts distinct characters, stopping as soon as $enough are found.
     */
    private function countUniqueChars(string $value, int $enough): int
    {
        $seen = [];
        foreach (mb_str_split($value) as $char) {
            $seen[$char] = true;
            if (count($seen) >= $enough) {
                break;
            }
        }

        return count($seen);
    }
}
