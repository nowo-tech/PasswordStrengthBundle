import { evaluatePassword, normalizeConditions } from './password-strength-lib';
import type { PasswordConditionsConfig } from './types';

const LOWERCASE = 'abcdefghijklmnopqrstuvwxyz';
const UPPERCASE = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
const DIGITS = '0123456789';
const DEFAULT_SPECIAL = '!@#$%^&*()_+-=[]{}|;:,.<>?';

/**
 * Secure random integer in [0, max).
 *
 * @param max Exclusive upper bound.
 * @returns Random index.
 */
function randomIndex(max: number): number {
  if (max <= 0) return 0;
  const array = new Uint32Array(1);
  crypto.getRandomValues(array);
  return array[0] % max;
}

/**
 * Pick one random character from a charset.
 *
 * @param charset Allowed characters.
 * @returns Single character.
 */
function pickChar(charset: string): string {
  return charset[randomIndex(charset.length)] ?? '';
}

/**
 * Fisher–Yates shuffle (in place).
 *
 * @param chars Character array to shuffle.
 */
function shuffle(chars: string[]): void {
  for (let i = chars.length - 1; i > 0; i -= 1) {
    const j = randomIndex(i + 1);
    [chars[i], chars[j]] = [chars[j], chars[i]];
  }
}

/**
 * Build allowed character pools from policy conditions.
 *
 * @param conditions Normalized password policy.
 * @returns Pools and combined alphabet.
 */
function buildPools(conditions: ReturnType<typeof normalizeConditions>): {
  requiredPools: string[];
  alphabet: string;
} {
  const requiredPools: string[] = [];
  const optionalPools: string[] = [];

  if (conditions.require_lowercase) {
    requiredPools.push(LOWERCASE);
    optionalPools.push(LOWERCASE);
  } else {
    optionalPools.push(LOWERCASE);
  }

  if (conditions.require_uppercase) {
    requiredPools.push(UPPERCASE);
    optionalPools.push(UPPERCASE);
  } else {
    optionalPools.push(UPPERCASE);
  }

  if (conditions.require_digit) {
    requiredPools.push(DIGITS);
    optionalPools.push(DIGITS);
  } else {
    optionalPools.push(DIGITS);
  }

  if (conditions.require_special) {
    const special = conditions.special_chars ?? DEFAULT_SPECIAL;
    requiredPools.push(special);
    optionalPools.push(special);
  }

  let alphabet = [...new Set(optionalPools.join('').split(''))].join('');
  if (conditions.disallow_whitespace) {
    alphabet = alphabet.replace(/\s/g, '');
  }

  if (alphabet.length === 0) {
    alphabet = LOWERCASE + UPPERCASE + DIGITS;
  }

  return { requiredPools, alphabet };
}

/**
 * Resolve target password length from conditions.
 *
 * @param conditions Normalized policy.
 * @returns Length for generated password.
 */
function resolveLength(conditions: ReturnType<typeof normalizeConditions>): number {
  const min = Math.max(conditions.min_length ?? 8, 8);
  const max = conditions.max_length ?? null;
  const floor = Math.max(min, conditions.min_unique_chars ?? 0, 1);

  if (max !== null && max > 0 && max >= floor) {
    const span = max - floor + 1;
    return floor + randomIndex(span);
  }

  return floor;
}

/**
 * Generate one password that satisfies the given conditions.
 *
 * @param rawConditions Policy from form or global config.
 * @param maxAttempts Retry limit when regex or not_contain reject a candidate.
 * @returns Generated password or empty string on failure.
 */
export function generatePassword(
  rawConditions: PasswordConditionsConfig,
  maxAttempts = 64,
): string {
  const conditions = normalizeConditions(rawConditions);

  for (let attempt = 0; attempt < maxAttempts; attempt += 1) {
    const { requiredPools, alphabet } = buildPools(conditions);
    const length = resolveLength(conditions);
    const chars: string[] = [];

    for (const pool of requiredPools) {
      chars.push(pickChar(pool));
    }

    while (chars.length < length) {
      chars.push(pickChar(alphabet));
    }

    while (chars.length > length) {
      chars.pop();
    }

    shuffle(chars);
    const candidate = chars.join('');

    const forbidden = conditions.not_contain ?? [];
    if (forbidden.some((fragment) => fragment && candidate.includes(fragment))) {
      continue;
    }

    const result = evaluatePassword(candidate, conditions);
    if (result.valid) {
      return candidate;
    }
  }

  return '';
}

/**
 * Generate multiple unique password suggestions.
 *
 * @param rawConditions Policy conditions.
 * @param count Number of suggestions (modal).
 * @returns List of valid passwords (may be shorter than count if generation fails).
 */
export function generatePasswords(
  rawConditions: PasswordConditionsConfig,
  count: number,
): string[] {
  const out: string[] = [];
  const seen = new Set<string>();
  const limit = Math.max(1, Math.min(count, 10));

  for (let i = 0; i < limit * 5 && out.length < limit; i += 1) {
    const pwd = generatePassword(rawConditions);
    if (pwd && !seen.has(pwd)) {
      seen.add(pwd);
      out.push(pwd);
    }
  }

  return out;
}
