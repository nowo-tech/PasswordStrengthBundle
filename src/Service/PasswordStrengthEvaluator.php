<?php

declare(strict_types=1);

namespace Nowo\PasswordStrengthBundle\Service;

use Nowo\PasswordStrengthBundle\Model\EvaluationResult;
use Nowo\PasswordStrengthBundle\Model\PasswordConditions;
use Nowo\PasswordStrengthBundle\Model\RequirementResult;

use function count;
use function mb_strlen;
use function preg_match;
use function preg_quote;
use function str_contains;

use const PREG_SPLIT_NO_EMPTY;

/**
 * Evaluates a password against configured conditions (server-side mirror of the TypeScript evaluator).
 */
final class PasswordStrengthEvaluator
{
    public function __construct(
        private readonly PasswordPatternBuilder $patternBuilder,
    ) {
    }

    public function evaluate(?string $password, PasswordConditions $conditions): EvaluationResult
    {
        $value        = $password ?? '';
        $requirements = [];
        $missing      = [];

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
            $escaped        = preg_quote($conditions->specialChars, '/');
            $met            = preg_match('/[' . $escaped . ']/u', $value) === 1;
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
            $unique         = count(array_unique(preg_split('//u', $value, -1, PREG_SPLIT_NO_EMPTY) ?: []));
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
            $met            = $value !== '' && preg_match($this->wrapRegex($conditions->regex), $value) === 1;
            $requirements[] = new RequirementResult('regex', 'requirement.regex', $met);
            if (!$met) {
                $missing[] = 'regex';
            }
        }

        $pattern = $this->patternBuilder->build($conditions);
        $valid   = $missing === [] && ($value !== '' || $requirements === []);

        if ($value !== '' && $pattern !== '.*' && $pattern !== '') {
            $patternValid = @preg_match('/' . $pattern . '/u', $value) === 1;
            if (!$patternValid && $missing === []) {
                $valid     = false;
                $missing[] = 'pattern';
            }
        }

        return new EvaluationResult($valid, $requirements, $missing, $pattern);
    }

    private function wrapRegex(string $regex): string
    {
        if (str_starts_with($regex, '/') && preg_match('#^/.+/[a-z]*$#i', $regex) === 1) {
            return $regex;
        }

        return '/' . $regex . '/u';
    }
}
