import { describe, expect, it } from 'vitest';
import { GENERATOR_MIN_LENGTH, generatePassword, generatePasswords } from './password-generator';
import { evaluatePassword } from './password-strength-lib';

describe('generatePassword', () => {
  it('generates password meeting medium policy', () => {
    const conditions = {
      min_length: 8,
      require_lowercase: true,
      require_uppercase: true,
      require_digit: true,
    };
    const pwd = generatePassword(conditions);
    expect(pwd.length).toBeGreaterThanOrEqual(8);
    const result = evaluatePassword(pwd, conditions);
    expect(result.valid).toBe(true);
  });

  it('generates password with special chars when required', () => {
    const conditions = {
      min_length: 12,
      require_lowercase: true,
      require_uppercase: true,
      require_digit: true,
      require_special: true,
    };
    const pwd = generatePassword(conditions);
    expect(evaluatePassword(pwd, conditions).valid).toBe(true);
  });

  it('respects max_length', () => {
    const conditions = {
      min_length: 6,
      max_length: 10,
      require_digit: true,
    };
    for (let i = 0; i < 20; i += 1) {
      const pwd = generatePassword(conditions);
      expect(pwd.length).toBeLessThanOrEqual(10);
      expect(pwd.length).toBeGreaterThanOrEqual(6);
    }
  });
});

describe('generator length floor', () => {
  it('generates at least 16 characters even when the policy asks for fewer', () => {
    expect(GENERATOR_MIN_LENGTH).toBe(16);
    expect(generatePassword({ min_length: 8, require_digit: true }).length).toBe(16);
    expect(generatePassword({ min_length: 20, require_digit: true }).length).toBe(20);
  });

  it('stays within a max_length below the floor', () => {
    expect(generatePassword({ min_length: 6, max_length: 10, require_digit: true }).length).toBe(10);
  });

  it('picks a length between the floor and max_length', () => {
    for (let i = 0; i < 20; i += 1) {
      const length = generatePassword({ max_length: 20, require_digit: true }).length;
      expect(length).toBeGreaterThanOrEqual(16);
      expect(length).toBeLessThanOrEqual(20);
    }
  });
});

describe('generatePasswords', () => {
  it('returns multiple suggestions', () => {
    const list = generatePasswords({ min_length: 8, require_digit: true }, 3);
    expect(list.length).toBeGreaterThan(0);
    expect(list.length).toBeLessThanOrEqual(3);
  });
});
