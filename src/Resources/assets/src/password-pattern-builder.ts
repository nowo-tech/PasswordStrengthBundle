import type { PasswordConditionsConfig } from './types';

/**
 * Escape a character set for use inside `[...]` (mirror of PasswordPatternBuilder::escapeForCharClass()).
 *
 * Every syntax character, `-` and `/`, and the class-set punctuators that the `v` flag reserves when doubled are
 * escaped, so the result is valid in a plain RegExp, in PCRE and in the HTML `pattern` attribute (compiled with `v`).
 * Without escaping `-`, the default set `…+-=…` would form the range `+`..`=` and accept digits as special characters.
 *
 * @param value Raw special-character set from configuration.
 * @returns Escaped string safe for `[...]`.
 */
export function escapeForCharClass(value: string): string {
  return value.replace(/[\\^$.*+?()[\]{}|/\-&!#%,:;<=>@`~]/g, '\\$&');
}

/**
 * Escape a literal fragment for use outside a character class (mirror of PasswordPatternBuilder::escapeLiteral()).
 *
 * Only syntax characters and `/` are escaped: other identity escapes (e.g. `\-`, `\!`) are invalid in the `u`/`v`
 * modes used for the HTML `pattern` attribute.
 *
 * @param value Raw fragment.
 * @returns Escaped fragment.
 */
export function escapeLiteral(value: string): string {
  return value.replace(/[\\^$.*+?()[\]{}|/]/g, '\\$&');
}

/**
 * Build an HTML5 pattern from password conditions (positive lookaheads).
 *
 * @param conditions Normalized password policy conditions.
 * @returns Pattern string without delimiters.
 */
export function buildPasswordPattern(conditions: PasswordConditionsConfig): string {
  const lookaheads: string[] = [];

  if (conditions.require_lowercase || conditions.lowercase) {
    lookaheads.push('(?=.*[a-z])');
  }
  if (conditions.require_uppercase || conditions.uppercase) {
    lookaheads.push('(?=.*[A-Z])');
  }
  if (conditions.require_digit || conditions.digit) {
    lookaheads.push('(?=.*\\d)');
  }
  if (conditions.require_special || conditions.special) {
    const chars = escapeForCharClass(conditions.special_chars ?? '!@#$%^&*()_+-=[]{}|;:,.<>?');
    lookaheads.push(`(?=.*[${chars}])`);
  }
  if (conditions.disallow_whitespace || conditions.no_whitespace) {
    lookaheads.push('(?!.*\\s)');
  }
  for (const fragment of conditions.not_contain ?? []) {
    if (fragment) {
      lookaheads.push(`(?!.*${escapeLiteral(fragment)})`);
    }
  }

  const min = Math.max(0, conditions.min_length ?? 0);
  const max = conditions.max_length ?? null;
  const lengthQuantifier =
    max !== null && max > 0 ? `{${min},${max}}` : min > 0 ? `{${min},}` : '*';

  if (conditions.regex) {
    return toHtmlPattern(conditions.regex);
  }

  const body = conditions.disallow_whitespace || conditions.no_whitespace ? '\\S' : '.';
  if (lookaheads.length === 0 && min === 0 && max === null) {
    return '.*';
  }

  return `^${lookaheads.join('')}${body}${lengthQuantifier}$`;
}

/**
 * Convert a configured regex into an HTML5 `pattern` value (mirror of PasswordPatternBuilder::toHtmlPattern()).
 *
 * Bare patterns are returned unchanged; `/body/flags` is stripped to `body`. Flags other than `u` cannot be
 * expressed in an HTML pattern, so an empty string is returned and the attribute should be omitted.
 *
 * @param regex Pattern from configuration (with or without delimiters).
 * @returns Pattern without delimiters, or '' when it cannot be represented.
 */
export function toHtmlPattern(regex: string): string {
  const match = /^\/(.+)\/([a-zA-Z]*)$/s.exec(regex);
  if (!match) {
    return regex;
  }
  return match[2].replace(/u/g, '') === '' ? match[1] : '';
}
