<?php

declare(strict_types=1);

namespace Nowo\PasswordStrengthBundle\Service;

use Nowo\PasswordStrengthBundle\Model\PasswordConditions;

use function count;
use function implode;
use function preg_quote;
use function sprintf;

/**
 * Builds an HTML5 pattern attribute from password conditions using positive lookaheads.
 */
final class PasswordPatternBuilder
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
            $escaped = preg_quote($conditions->specialChars, '/');
            $lookaheads[] = '(?=.*[' . $escaped . '])';
        }
        if ($conditions->disallowWhitespace) {
            $lookaheads[] = '(?!.*\s)';
        }
        foreach ($conditions->notContain as $fragment) {
            $lookaheads[] = '(?!.*' . preg_quote($fragment, '/') . ')';
        }
        if ($conditions->minUniqueChars > 0) {
            $lookaheads[] = sprintf('(?=(?:.*(.)((?!\1).)){0,}%d)', max(0, $conditions->minUniqueChars - 1));
        }

        $min = max(0, $conditions->minLength);
        $max = $conditions->maxLength;
        $lengthQuantifier = $max !== null && $max > 0
            ? '{' . $min . ',' . $max . '}'
            : ($min > 0 ? '{' . $min . ',}' : '*');

        if ($conditions->regex !== null) {
            return $conditions->regex;
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
}
