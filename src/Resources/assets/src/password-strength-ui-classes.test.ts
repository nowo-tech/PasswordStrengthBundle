import { readFileSync } from 'node:fs';
import { resolve } from 'node:path';
import { afterEach, describe, expect, it } from 'vitest';
import { initPasswordStrengthContainer } from './password-strength';

function config(extra: Record<string, unknown>): string {
  return JSON.stringify({
    conditions: { min_length: 8, require_digit: true },
    feedbackPosition: 'below',
    showRequirements: true,
    liveFeedback: true,
    uiFramework: 'tailwind2',
    generatorMode: 'modal',
    generatorCount: 2,
    labels: [],
    ...extra,
  });
}

function renderField(extra: Record<string, unknown>): HTMLElement {
  document.body.innerHTML = `
    <nowo-password-strength data-password-strength-field>
      <div class="password-strength-input-row">
        <div class="password-strength-input-grow">
          <input type="password" class="password-strength-widget" data-password-strength-config-value='${config(extra)}'>
        </div>
        <button type="button" data-password-strength-generate>Generate</button>
      </div>
    </nowo-password-strength>`;

  return document.querySelector('nowo-password-strength') as HTMLElement;
}

function typeInto(field: HTMLElement, value: string): void {
  const input = field.querySelector('input') as HTMLInputElement;
  input.value = value;
  input.dispatchEvent(new Event('input', { bubbles: true }));
}

describe('ui_classes (2.11)', () => {
  afterEach(() => {
    document.body.innerHTML = '';
  });

  it('keeps the preset classes when no uiClasses are given', () => {
    const field = renderField({});
    initPasswordStrengthContainer(field);
    typeInto(field, 'abc');

    expect(field.parentElement?.querySelector('ul')?.className).toBe('password-strength-requirements space-y-1 text-sm');
    expect(document.querySelector('[data-requirement-id]')?.className).toContain('text-red-600');
  });

  it('replaces the preset classes key by key', () => {
    const field = renderField({
      uiClasses: { wrapper: 'host-feedback', list: 'host-list', itemMet: 'host-met', itemUnmet: 'host-unmet', btnPrimary: 'host-btn host-btn--primary', btnSecondary: 'host-btn' },
    });
    initPasswordStrengthContainer(field);
    typeInto(field, 'abcdefgh');

    expect(document.querySelector('[data-password-strength-feedback]')?.className).toBe('host-feedback');
    expect(document.querySelector('[data-password-strength-feedback] ul')?.className).toBe('host-list');
    const items = [...document.querySelectorAll('[data-requirement-id]')].map((li) => li.className);
    expect(items).toContain('host-met');
    expect(items).toContain('host-unmet');

    (field.querySelector('[data-password-strength-generate]') as HTMLButtonElement).click();
    const modal = document.querySelector('.password-strength-modal') as HTMLElement;
    expect(modal).not.toBeNull();
    const classes = [...modal.querySelectorAll('button')].map((button) => button.className);
    expect(classes).toContain('host-btn host-btn--primary');
    expect(classes).toContain('host-btn');
    expect(classes.join(' ')).not.toContain('bg-blue-600');
  });
});

describe('password-strength.css host tokens (2.11)', () => {
  const css = readFileSync(resolve(__dirname, '../css/password-strength.css'), 'utf8');

  it('never declares a host token and reads each one with a fallback', () => {
    const reads = [...css.matchAll(/var\((--nowo-password-strength-[a-z0-9-]+)([,)])/g)];
    expect(reads.length).toBeGreaterThan(20);
    for (const [, name, next] of reads) {
      expect(next).toBe(',');
      expect(css).not.toMatch(new RegExp(`${name}\\s*:`));
    }
  });

  it('keeps the earlier values as fallbacks', () => {
    expect(css).toContain('background:var(--nowo-password-strength-modal-bg,#fff)');
    expect(css).toContain('max-width:var(--nowo-password-strength-modal-max-width,32rem)');
    expect(css).toContain('background:var(--nowo-password-strength-overlay,rgba(0,0,0,.45))');
    expect(css).toContain('color:var(--nowo-password-strength-modal-hint-color,#6c757d)');
    expect(css).toContain('margin-bottom:var(--nowo-password-strength-modal-item-spacing,.5rem)');
  });
});
