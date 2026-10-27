import { readFileSync } from 'node:fs';
import { resolve } from 'node:path';
import { describe, expect, it } from 'vitest';
import { buildPasswordPattern, escapeForCharClass, escapeLiteral } from './password-pattern-builder';
import { evaluatePassword } from './password-strength-lib';
import type { PasswordConditionsConfig } from './types';

/**
 * Shared PHP/TypeScript vectors (tests/Fixtures/password-vectors.json, also run by SharedVectorsTest.php).
 */
interface VectorCase {
  password?: string;
  repeat?: string;
  times?: number;
  valid: boolean;
  missing: string[];
}

interface Fixture {
  escape: { input: string; class: string; literal: string }[];
  vectors: { name: string; conditions: PasswordConditionsConfig; pattern: string; cases: VectorCase[] }[];
}

const fixture = JSON.parse(
  readFileSync(resolve(process.cwd(), 'tests/Fixtures/password-vectors.json'), 'utf8'),
) as Fixture;

describe('shared escape vectors', () => {
  it.each(fixture.escape)('escapes $input like PHP', (row) => {
    expect(escapeForCharClass(row.input)).toBe(row.class);
    expect(escapeLiteral(row.input)).toBe(row.literal);
    // Valid in the browser's HTML `pattern` mode (`v` flag) and matches the input literally.
    expect(new RegExp(`^(?:${row.literal})$`, 'v').test(row.input)).toBe(true);
    expect(new RegExp(`^(?:[${row.class}]+)$`, 'v').test(row.input)).toBe(true);
  });
});

describe('shared policy vectors', () => {
  for (const vector of fixture.vectors) {
    it(`builds the same pattern as PHP: ${vector.name}`, () => {
      const pattern = buildPasswordPattern(vector.conditions);
      expect(pattern).toBe(vector.pattern);
      // The browser compiles `pattern` as ^(?:…)$ with the `v` flag: it must stay valid there.
      expect(() => new RegExp(`^(?:${pattern})$`, 'v')).not.toThrow();
    });

    vector.cases.forEach((c, i) => {
      it(`evaluates like PHP: ${vector.name} #${i}`, () => {
        const password = c.repeat !== undefined ? c.repeat.repeat(c.times ?? 0) : (c.password ?? '');
        const result = evaluatePassword(password, vector.conditions);
        expect(result.valid).toBe(c.valid);
        expect(result.missing).toEqual(c.missing);
      });
    });
  }
});
