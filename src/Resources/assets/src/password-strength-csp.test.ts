import { afterEach, beforeEach, describe, expect, it } from 'vitest';
import { BASE_STYLE_ID, injectBaseStyles, resolveCspNonce, TAG_NOWO_PASSWORD_STRENGTH } from './password-strength';

/**
 * Regression: the injected base <style> had no nonce, so hosts with a nonce-based
 * style-src (style-src-elem) got a CSP violation on every page with the widget.
 */
describe('CSP-safe base styles', () => {
  const reset = (): void => {
    document.head.innerHTML = '';
    document.body.innerHTML = '';
  };
  // The module auto-runs on import (no nonce source yet): start every case from an empty document.
  beforeEach(reset);
  afterEach(reset);

  const injected = (): HTMLStyleElement | null => document.getElementById(BASE_STYLE_ID) as HTMLStyleElement | null;

  it('takes the nonce from data-nonce on the widget root', () => {
    document.body.innerHTML = `<${TAG_NOWO_PASSWORD_STRENGTH} data-nonce="root-nonce"><input id="pw"></${TAG_NOWO_PASSWORD_STRENGTH}>`;
    injectBaseStyles(document.getElementById('pw'));

    const style = injected();
    expect(style).not.toBeNull();
    expect(style?.nonce).toBe('root-nonce');
    expect(style?.textContent).toContain('.password-strength-input-row');
  });

  it('finds a widget root with data-nonce when called without a root', () => {
    document.body.innerHTML = '<div data-password-strength-field data-nonce="field-nonce"></div>';
    injectBaseStyles();

    expect(injected()?.nonce).toBe('field-nonce');
  });

  it('falls back to <meta name="csp-nonce">', () => {
    document.head.innerHTML = '<meta name="csp-nonce" content="meta-nonce">';
    expect(resolveCspNonce(null)).toBe('meta-nonce');
    injectBaseStyles();

    expect(injected()?.nonce).toBe('meta-nonce');
  });

  it('reads the nonce attribute of the meta element when it has no content', () => {
    document.head.innerHTML = '<meta name="csp-nonce" nonce="meta-attr-nonce">';

    expect(resolveCspNonce(null)).toBe('meta-attr-nonce');
  });

  it('falls back to the loading script nonce', () => {
    expect(resolveCspNonce(null, 'script-nonce')).toBe('script-nonce');
    expect(resolveCspNonce(null)).toBe('');
  });

  it('injects without a nonce attribute when none is available', () => {
    injectBaseStyles();

    expect(injected()?.hasAttribute('nonce')).toBe(false);
  });

  it('keeps a host pre-declared <style id> untouched (no second element)', () => {
    document.head.innerHTML = `<style id="${BASE_STYLE_ID}" nonce="host"></style>`;
    injectBaseStyles();

    expect(document.querySelectorAll(`#${BASE_STYLE_ID}`)).toHaveLength(1);
    expect(injected()?.textContent).toBe('');
  });

  it('does not inject when password-strength.css is linked', () => {
    document.head.innerHTML = '<link rel="stylesheet" href="/bundles/passwordstrength/password-strength.css">';
    injectBaseStyles();

    expect(injected()).toBeNull();
  });

  it('does not inject when the stylesheet marker is present (bundled copy)', () => {
    document.head.innerHTML = '<style>:root{--nowo-password-strength-css:1}</style>';
    injectBaseStyles();

    expect(injected()).toBeNull();
  });
});
