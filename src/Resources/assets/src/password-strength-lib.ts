import { buildPasswordPattern } from './password-pattern-builder';
import type {
  EvaluationResult,
  PasswordConditionsConfig,
  RequirementResult,
} from './types';

/**
 * Normalize raw condition config (aliases and defaults).
 *
 * @param data Raw conditions from PHP or form config.
 * @returns Normalized conditions object.
 */
export function normalizeConditions(data: PasswordConditionsConfig): PasswordConditionsConfig {
  return {
    min_length: Math.max(0, data.min_length ?? 0),
    max_length: data.max_length ?? null,
    require_lowercase: Boolean(data.require_lowercase ?? data.lowercase),
    require_uppercase: Boolean(data.require_uppercase ?? data.uppercase),
    require_digit: Boolean(data.require_digit ?? data.digit),
    require_special: Boolean(data.require_special ?? data.special),
    special_chars: data.special_chars ?? '!@#$%^&*()_+-=[]{}|;:,.<>?',
    disallow_whitespace: Boolean(data.disallow_whitespace ?? data.no_whitespace),
    not_contain: Array.isArray(data.not_contain) ? data.not_contain.filter(Boolean) : [],
    regex: data.regex ?? null,
    regex_message: data.regex_message ?? null,
    min_unique_chars: Math.max(0, data.min_unique_chars ?? 0),
  };
}

/**
 * Evaluate a password against configured conditions.
 *
 * @param password Value to validate (may be empty).
 * @param rawConditions Policy conditions from configuration.
 * @returns Evaluation with per-requirement status and HTML pattern.
 */
export function evaluatePassword(
  password: string,
  rawConditions: PasswordConditionsConfig,
): EvaluationResult {
  const conditions = normalizeConditions(rawConditions);
  const value = password ?? '';
  const requirements: RequirementResult[] = [];
  const missing: string[] = [];

  if (conditions.min_length && conditions.min_length > 0) {
    const length = [...value].length;
    const met = length >= conditions.min_length;
    requirements.push({
      id: 'min_length',
      labelKey: 'requirement.min_length',
      met,
      current: length,
      required: conditions.min_length,
    });
    if (!met) missing.push('min_length');
  }

  if (conditions.max_length && conditions.max_length > 0) {
    const length = [...value].length;
    const met = length <= conditions.max_length;
    requirements.push({
      id: 'max_length',
      labelKey: 'requirement.max_length',
      met,
      current: length,
      required: conditions.max_length,
    });
    if (!met) missing.push('max_length');
  }

  if (conditions.require_lowercase) {
    const met = /[a-z]/.test(value);
    requirements.push({ id: 'require_lowercase', labelKey: 'requirement.lowercase', met });
    if (!met) missing.push('require_lowercase');
  }

  if (conditions.require_uppercase) {
    const met = /[A-Z]/.test(value);
    requirements.push({ id: 'require_uppercase', labelKey: 'requirement.uppercase', met });
    if (!met) missing.push('require_uppercase');
  }

  if (conditions.require_digit) {
    const met = /\d/.test(value);
    requirements.push({ id: 'require_digit', labelKey: 'requirement.digit', met });
    if (!met) missing.push('require_digit');
  }

  if (conditions.require_special) {
    const escaped = (conditions.special_chars ?? '').replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
    const met = new RegExp(`[${escaped}]`).test(value);
    requirements.push({ id: 'require_special', labelKey: 'requirement.special', met });
    if (!met) missing.push('require_special');
  }

  if (conditions.disallow_whitespace) {
    const met = value === '' || /^\S+$/.test(value);
    requirements.push({ id: 'disallow_whitespace', labelKey: 'requirement.no_whitespace', met });
    if (!met) missing.push('disallow_whitespace');
  }

  for (const fragment of conditions.not_contain ?? []) {
    const met = value === '' || !value.includes(fragment);
    requirements.push({
      id: `not_contain_${fragment}`,
      labelKey: 'requirement.not_contain',
      met,
    });
    if (!met) missing.push('not_contain');
  }

  if (conditions.min_unique_chars && conditions.min_unique_chars > 0) {
    const unique = new Set([...value]).size;
    const met = unique >= conditions.min_unique_chars;
    requirements.push({
      id: 'min_unique_chars',
      labelKey: 'requirement.min_unique_chars',
      met,
      current: unique,
      required: conditions.min_unique_chars,
    });
    if (!met) missing.push('min_unique_chars');
  }

  if (conditions.regex) {
    const regex = wrapRegex(conditions.regex);
    const met = value !== '' && regex.test(value);
    requirements.push({ id: 'regex', labelKey: 'requirement.regex', met });
    if (!met) missing.push('regex');
  }

  const pattern = buildPasswordPattern(conditions);
  let valid = missing.length === 0;

  if (value !== '' && pattern !== '.*' && pattern !== '') {
    try {
      const patternValid = new RegExp(pattern).test(value);
      if (!patternValid && missing.length === 0) {
        valid = false;
        missing.push('pattern');
      }
    } catch {
      // ignore invalid pattern at runtime
    }
  }

  return { valid, missing, pattern, requirements };
}

/**
 * @param regex Pattern from configuration (with or without delimiters).
 * @returns JavaScript RegExp instance.
 */
function wrapRegex(regex: string): RegExp {
  if (regex.startsWith('/') && /\/[a-z]*$/i.test(regex)) {
    return new RegExp(regex.slice(1, regex.lastIndexOf('/')), regex.slice(regex.lastIndexOf('/') + 1));
  }
  return new RegExp(regex);
}

/**
 * Default English labels for requirement keys (overridden by Twig translations when rendered).
 */
export const DEFAULT_LABELS: Record<string, string> = {
  'requirement.min_length': 'At least %count% characters',
  'requirement.max_length': 'At most %count% characters',
  'requirement.lowercase': 'At least one lowercase letter',
  'requirement.uppercase': 'At least one uppercase letter',
  'requirement.digit': 'At least one number',
  'requirement.special': 'At least one special character',
  'requirement.no_whitespace': 'No whitespace allowed',
  'requirement.not_contain': 'Must not contain forbidden text',
  'requirement.min_unique_chars': 'At least %count% unique characters',
  'requirement.regex': 'Must match the required pattern',
};

/**
 * Resolve a human-readable label for a requirement.
 *
 * @param labelKey Translation key.
 * @param required Optional numeric placeholder.
 * @param labels Optional label map from data attributes.
 * @returns Display label.
 */
export function formatRequirementLabel(
  labelKey: string,
  required?: number,
  labels: Record<string, string> = DEFAULT_LABELS,
): string {
  const template = labels[labelKey] ?? labelKey;
  if (required !== undefined) {
    return template.replace('%count%', String(required));
  }
  return template;
}
