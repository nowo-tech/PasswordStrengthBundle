import { describe, expect, it } from 'vitest';
import { buildPasswordPattern } from './password-pattern-builder';
import { evaluatePassword } from './password-strength-lib';

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
});
