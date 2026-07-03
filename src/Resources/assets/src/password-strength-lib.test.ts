import { describe, expect, it } from 'vitest';
import { buildPasswordPattern } from './password-pattern-builder';
import {
  DEFAULT_LABELS,
  evaluatePassword,
  formatRequirementLabel,
} from './password-strength-lib';

describe('buildPasswordPattern', () => {
  it('builds lookahead pattern for medium policy', () => {
    const pattern = buildPasswordPattern({
      min_length: 8,
      require_lowercase: true,
      require_uppercase: true,
      require_digit: true,
    });
    expect(pattern).toContain('(?=.*[a-z])');
    expect(pattern).toContain('{8,}');
  });

  it('supports alias keys and returns wildcard when empty', () => {
    expect(buildPasswordPattern({})).toBe('.*');
    expect(
      buildPasswordPattern({
        lowercase: true,
        uppercase: true,
        digit: true,
        min_length: 6,
      }),
    ).toContain('(?=.*[a-z])');
  });

  it('handles max length, whitespace, not_contain, and regex override', () => {
    const pattern = buildPasswordPattern({
      min_length: 4,
      max_length: 10,
      no_whitespace: true,
      not_contain: ['admin'],
      require_special: true,
      special_chars: '!',
    });
    expect(pattern).toContain('{4,10}');
    expect(pattern).toContain('(?!.*\\s)');
    expect(pattern).toContain('(?!.*admin)');

    expect(buildPasswordPattern({ regex: '^[A-Z]+$' })).toBe('^[A-Z]+$');
  });
});

describe('evaluatePassword', () => {
  it('reports missing requirements', () => {
    const result = evaluatePassword('abc', {
      min_length: 8,
      require_uppercase: true,
      require_digit: true,
    });
    expect(result.valid).toBe(false);
    expect(result.missing).toContain('min_length');
    expect(result.missing).toContain('require_uppercase');
    expect(result.missing).toContain('require_digit');
  });

  it('accepts a valid strong password', () => {
    const result = evaluatePassword('MyStr0ng!Pass', {
      min_length: 8,
      require_lowercase: true,
      require_uppercase: true,
      require_digit: true,
      require_special: true,
    });
    expect(result.valid).toBe(true);
    expect(result.missing).toHaveLength(0);
  });

  it('evaluates max length, whitespace, not_contain, and unique chars', () => {
    expect(
      evaluatePassword('abcdefghij', { max_length: 5 }).missing,
    ).toContain('max_length');
    expect(
      evaluatePassword('a b', { disallow_whitespace: true }).missing,
    ).toContain('disallow_whitespace');
    expect(
      evaluatePassword('password1', { not_contain: ['password'] }).missing,
    ).toContain('not_contain');
    expect(
      evaluatePassword('aaaa', { min_unique_chars: 3 }).missing,
    ).toContain('min_unique_chars');
  });

  it('evaluates delimited and plain regex patterns', () => {
    expect(evaluatePassword('abc', { regex: '^[0-9]+$' }).missing).toContain('regex');
    expect(evaluatePassword('123', { regex: '^[0-9]+$' }).valid).toBe(true);
  });

  it('covers individual requirement branches', () => {
    expect(evaluatePassword('ABC', { require_lowercase: true }).missing).toContain('require_lowercase');
    expect(evaluatePassword('abc', { require_uppercase: true }).missing).toContain('require_uppercase');
    expect(evaluatePassword('abc', { require_digit: true }).missing).toContain('require_digit');
    expect(evaluatePassword('abc', { require_special: true, special_chars: '!' }).missing).toContain(
      'require_special',
    );
  });

  it('handles pattern mismatch when regex policy fails', () => {
    const result = evaluatePassword('Abcd1234', {
      min_length: 8,
      require_lowercase: true,
      require_uppercase: true,
      require_digit: true,
      regex: '^[0-9]+$',
    });
    expect(result.valid).toBe(false);
    expect(result.missing).toContain('regex');
  });
});

describe('formatRequirementLabel', () => {
  it('replaces count placeholder and falls back to key', () => {
    expect(formatRequirementLabel('requirement.min_length', 8)).toBe(
      DEFAULT_LABELS['requirement.min_length'].replace('%count%', '8'),
    );
    expect(formatRequirementLabel('custom.key')).toBe('custom.key');
  });
});
