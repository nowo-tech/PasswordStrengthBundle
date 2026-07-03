import type { PasswordConditionsConfig } from './types';

/**
 * Escape characters for use inside a RegExp character class.
 *
 * @param value Raw special-character set from configuration.
 * @returns Escaped string safe for `[...]`.
 */
function escapeForCharClass(value: string): string {
  return value.replace(/[\\^$.*+?()[\]{}|]/g, '\\$&');
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
      lookaheads.push(`(?!.*${fragment.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')})`);
    }
  }

  const min = Math.max(0, conditions.min_length ?? 0);
  const max = conditions.max_length ?? null;
  const lengthQuantifier =
    max !== null && max > 0 ? `{${min},${max}}` : min > 0 ? `{${min},}` : '*';

  if (conditions.regex) {
    return conditions.regex;
  }

  const body = conditions.disallow_whitespace || conditions.no_whitespace ? '\\S' : '.';
  if (lookaheads.length === 0 && min === 0 && max === null) {
    return '.*';
  }

  return `^${lookaheads.join('')}${body}${lengthQuantifier}$`;
}
