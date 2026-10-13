import { afterEach, describe, expect, it } from 'vitest';
import { initPasswordStrengthContainer } from './password-strength';

const CONFIG = JSON.stringify({
  conditions: { min_length: 8, require_lowercase: true, require_uppercase: true, require_digit: true },
  feedbackPosition: 'below',
  showRequirements: false,
  liveFeedback: false,
  uiFramework: 'bootstrap5',
  generatorMode: 'input',
  generatorCount: 3,
  labels: [],
});

/** Markup rendered by password_strength_widget + PasswordToggleBundle toggle_password_widget. */
function renderToggleField(toggle: '0' | '1' = '1'): HTMLElement {
  document.body.innerHTML = `
    <nowo-password-strength data-password-strength-field>
      <div class="password-strength-input-row password-strength-input-row--toggle">
        <div class="password-strength-input-grow">
          <nowo-password-toggle class="input-group form-password-toggle" data-nowo-password-toggle>
            <input type="password" id="pwd" class="password-strength-widget"
                   data-password-strength-config-value='${CONFIG}'
                   data-password-strength-generator-mode="input"
                   data-password-strength-toggle="${toggle}"
                   data-password-strength-toggle-hidden-label="Hide">
            <span class="input-group-text cursor-pointer" role="button" data-nowo-password-toggle-target="button" aria-label="Show">
              <svg class="icon-hidden"></svg><svg class="icon-visible"></svg>
            </span>
          </nowo-password-toggle>
        </div>
        <button type="button" data-password-strength-generate>Generate</button>
      </div>
    </nowo-password-strength>`;

  return document.querySelector('nowo-password-strength') as HTMLElement;
}

describe('generator + PasswordToggleBundle (regression)', () => {
  afterEach(() => {
    document.body.innerHTML = '';
  });

  it('reveals the generated password via is-password-visible, without inline icon styles', () => {
    const field = renderToggleField();
    initPasswordStrengthContainer(field);

    (field.querySelector('[data-password-strength-generate]') as HTMLButtonElement).click();

    const input = field.querySelector('input') as HTMLInputElement;
    const button = field.querySelector('[data-nowo-password-toggle-target="button"]') as HTMLElement;
    expect(input.type).toBe('text');
    expect(input.value).not.toBe('');
    expect(button.classList.contains('is-password-visible')).toBe(true);
    expect(button.getAttribute('aria-label')).toBe('Hide');
    field.querySelectorAll<SVGElement>('svg').forEach((icon) => {
      expect(icon.getAttribute('style')).toBeNull();
    });
  });

  it('leaves the toggle button alone when the toggle is not active', () => {
    const field = renderToggleField('0');
    initPasswordStrengthContainer(field);

    (field.querySelector('[data-password-strength-generate]') as HTMLButtonElement).click();

    const button = field.querySelector('[data-nowo-password-toggle-target="button"]') as HTMLElement;
    expect((field.querySelector('input') as HTMLInputElement).type).toBe('text');
    expect(button.classList.contains('is-password-visible')).toBe(false);
    expect(button.getAttribute('aria-label')).toBe('Show');
  });
});
