import { afterEach, describe, expect, it, vi } from 'vitest';
import {
  ensureNowoPasswordStrengthDefined,
  TAG_NOWO_PASSWORD_STRENGTH,
} from './password-strength';

describe('nowo-password-strength-element', () => {
  afterEach(() => {
    vi.restoreAllMocks();
    document.body.innerHTML = '';
  });

  it('defines the custom element once', () => {
    ensureNowoPasswordStrengthDefined();
    const defined = customElements.get(TAG_NOWO_PASSWORD_STRENGTH);
    expect(defined).toBeDefined();
    ensureNowoPasswordStrengthDefined();
    expect(customElements.get(TAG_NOWO_PASSWORD_STRENGTH)).toBe(defined);
  });

  it('no-ops when customElements is unavailable', () => {
    const original = globalThis.customElements;
    Object.defineProperty(globalThis, 'customElements', { configurable: true, value: undefined });
    expect(() => ensureNowoPasswordStrengthDefined()).not.toThrow();
    Object.defineProperty(globalThis, 'customElements', { configurable: true, value: original });
  });
});
