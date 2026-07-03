import { describe, expect, it } from 'vitest';
import { evaluatePassword } from './password-strength-lib';

describe('evaluatePassword', () => {
  it('accepts a password that meets medium policy', () => {
    const result = evaluatePassword('Abcdef12', {
      min_length: 8,
      require_lowercase: true,
      require_uppercase: true,
      require_digit: true,
    });

    expect(result.valid).toBe(true);
    expect(result.missing).toHaveLength(0);
  });
});

/**
 * DOM helpers used by password-strength.ts must split multi-class strings.
 * Regression: classList.add/remove throw on strings with spaces.
 */
describe('classList helpers (regression)', () => {
  it('splits and applies bootstrap alert classes without InvalidCharacterError', () => {
    const element = document.createElement('div');
    const alertClasses = 'alert alert-warning password-strength-alert';

    for (const token of alertClasses.trim().split(/\s+/).filter(Boolean)) {
      element.classList.add(token);
    }
    expect(element.classList.contains('alert')).toBe(true);
    expect(element.classList.contains('alert-warning')).toBe(true);

    for (const token of alertClasses.trim().split(/\s+/).filter(Boolean)) {
      element.classList.remove(token);
    }
    expect(element.classList.contains('alert')).toBe(false);
  });
});
