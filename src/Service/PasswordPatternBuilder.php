<?php

declare(strict_types=1);

namespace Nowo\PasswordStrengthBundle\Service;

use Nowo\PasswordStrengthBundle\Model\PasswordConditions;

use function count;
use function implode;
use function preg_match;
use function preg_quote;
use function str_replace;

/**
 * Builds an HTML5 pattern attribute from password conditions using positive lookaheads.
 */
final readonly class PasswordPatternBuilder
{
    public function build(PasswordConditions $conditions): string
    {
        $lookaheads = [];

        if ($conditions->requireLowercase) {
            $lookaheads[] = '(?=.*[a-z])';
        }
        if ($conditions->requireUppercase) {
            $lookaheads[] = '(?=.*[A-Z])';
        }
        if ($conditions->requireDigit) {
            $lookaheads[] = '(?=.*\d)';
        }
        if ($conditions->requireSpecial) {
            $escaped      = preg_quote($conditions->specialChars, '/');
            $lookaheads[] = '(?=.*[' . $escaped . '])';
        }
        if ($conditions->disallowWhitespace) {
            $lookaheads[] = '(?!.*\s)';
        }
        foreach ($conditions->notContain as $fragment) {
            $lookaheads[] = '(?!.*' . preg_quote($fragment, '/') . ')';
        }
        // min_unique_chars has no portable lookahead form; it is enforced by the evaluators (PHP + TS).

        $min              = max(0, $conditions->minLength);
        $max              = $conditions->maxLength;
        $lengthQuantifier = $max !== null && $max > 0
            ? '{' . $min . ',' . $max . '}'
            : ($min > 0 ? '{' . $min . ',}' : '*');

        if ($conditions->regex !== null) {
            return self::toHtmlPattern($conditions->regex);
        }

        $body = '.';
        if ($conditions->disallowWhitespace) {
            $body = '\S';
        }

        $pattern = '^' . implode('', $lookaheads) . $body . $lengthQuantifier . '$';

        if (count($lookaheads) === 0 && $min === 0 && $max === null) {
            return '.*';
        }

        return $pattern;
    }

    /**
     * Converts a configured regex into an HTML5 pattern attribute value.
     *
     * Bare patterns (`^[A-Z]+$`) are returned unchanged. Delimited patterns (`/^[A-Z]+$/`) are stripped of
     * their delimiters; when they carry flags other than `u` (e.g. `/…/i`), the semantics cannot be expressed
     * in an HTML pattern, so an empty string is returned and the `pattern` attribute is omitted.
     */
    public static function toHtmlPattern(string $regex): string
    {
        $delimited = self::splitDelimited($regex);
        if ($delimited === null) {
            return $regex;
        }

        [$body, $flags] = $delimited;

        return str_replace('u', '', $flags) === '' ? $body : '';
    }

    /**
     * Returns the regex ready for preg_match(): delimited input is used as given, bare input is wrapped in `/…/u`.
     */
    public static function toPcre(string $regex): string
    {
        return self::splitDelimited($regex) !== null ? $regex : '/' . $regex . '/u';
    }

    /**
     * @return array{0: string, 1: string}|null [body, flags] when the regex is written as `/body/flags`
     */
    private static function splitDelimited(string $regex): ?array
    {
        if (preg_match('#^/(.+)/([a-zA-Z]*)$#s', $regex, $m) !== 1) {
            return null;
        }

        return [$m[1], $m[2]];
    }
}
