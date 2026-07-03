import { createBundleLogger } from './logger';
import { generatePassword, generatePasswords } from './password-generator';
import {
  DEFAULT_LABELS,
  evaluatePassword,
  formatRequirementLabel,
} from './password-strength-lib';
import type { GeneratorMode, PasswordStrengthConfig, PasswordStrengthLabels, UiFramework } from './types';

declare const __PASSWORD_STRENGTH_BUILD_TIME__: string;

const log = createBundleLogger('password-strength', {
  buildTime:
    typeof __PASSWORD_STRENGTH_BUILD_TIME__ !== 'undefined'
      ? __PASSWORD_STRENGTH_BUILD_TIME__
      : undefined,
});

let stylesInjected = false;

/** CSS class map per UI framework (framework-agnostic hooks). */
const FRAMEWORK_CLASSES: Record<
  UiFramework,
  {
    wrapper: string;
    list: string;
    itemMet: string;
    itemUnmet: string;
    alert: string;
    btn: string;
    btnPrimary: string;
    btnSecondary: string;
  }
> = {
  default: {
    wrapper: 'password-strength-feedback',
    list: 'password-strength-requirements',
    itemMet: 'password-strength-requirement password-strength-requirement--met',
    itemUnmet: 'password-strength-requirement password-strength-requirement--unmet',
    alert: 'password-strength-alert',
    btn: 'password-strength-generate-btn',
    btnPrimary: 'password-strength-btn password-strength-btn--primary',
    btnSecondary: 'password-strength-btn password-strength-btn--secondary',
  },
  bootstrap3: {
    wrapper: 'password-strength-feedback',
    list: 'list-unstyled password-strength-requirements',
    itemMet: 'text-success password-strength-requirement password-strength-requirement--met',
    itemUnmet: 'text-danger password-strength-requirement password-strength-requirement--unmet',
    alert: 'alert alert-warning password-strength-alert',
    btn: 'btn btn-default btn-sm password-strength-generate-btn',
    btnPrimary: 'btn btn-primary btn-sm',
    btnSecondary: 'btn btn-default btn-sm',
  },
  bootstrap4: {
    wrapper: 'password-strength-feedback',
    list: 'list-unstyled password-strength-requirements',
    itemMet: 'text-success password-strength-requirement password-strength-requirement--met',
    itemUnmet: 'text-danger password-strength-requirement password-strength-requirement--unmet',
    alert: 'alert alert-warning password-strength-alert',
    btn: 'btn btn-outline-secondary btn-sm password-strength-generate-btn',
    btnPrimary: 'btn btn-primary btn-sm',
    btnSecondary: 'btn btn-outline-secondary btn-sm',
  },
  bootstrap5: {
    wrapper: 'password-strength-feedback',
    list: 'list-unstyled password-strength-requirements mb-0',
    itemMet: 'text-success password-strength-requirement password-strength-requirement--met',
    itemUnmet: 'text-danger password-strength-requirement password-strength-requirement--unmet',
    alert: 'alert alert-warning password-strength-alert',
    btn: 'btn btn-outline-secondary btn-sm password-strength-generate-btn',
    btnPrimary: 'btn btn-primary btn-sm',
    btnSecondary: 'btn btn-outline-secondary btn-sm',
  },
  tailwind2: {
    wrapper: 'password-strength-feedback mt-2',
    list: 'password-strength-requirements space-y-1 text-sm',
    itemMet: 'text-green-600 password-strength-requirement password-strength-requirement--met',
    itemUnmet: 'text-red-600 password-strength-requirement password-strength-requirement--unmet',
    alert: 'password-strength-alert bg-yellow-50 border border-yellow-200 text-yellow-800 p-2 rounded',
    btn: 'password-strength-generate-btn px-3 py-2 text-sm border rounded bg-white hover:bg-gray-50',
    btnPrimary: 'px-3 py-1 text-sm rounded bg-blue-600 text-white hover:bg-blue-700',
    btnSecondary: 'px-3 py-1 text-sm rounded border bg-white hover:bg-gray-50',
  },
  foundation5: {
    wrapper: 'password-strength-feedback',
    list: 'no-bullet password-strength-requirements',
    itemMet: 'success password-strength-requirement password-strength-requirement--met',
    itemUnmet: 'alert password-strength-requirement password-strength-requirement--unmet',
    alert: 'alert-box warning password-strength-alert',
    btn: 'button secondary small password-strength-generate-btn',
    btnPrimary: 'button small',
    btnSecondary: 'button secondary small',
  },
  foundation6: {
    wrapper: 'password-strength-feedback',
    list: 'no-bullet password-strength-requirements',
    itemMet: 'success password-strength-requirement password-strength-requirement--met',
    itemUnmet: 'alert password-strength-requirement password-strength-requirement--unmet',
    alert: 'callout warning password-strength-alert',
    btn: 'button hollow small password-strength-generate-btn',
    btnPrimary: 'button small',
    btnSecondary: 'button hollow small',
  },
};

/**
 * Parse JSON config from a data attribute.
 *
 * @param raw JSON string from `data-password-strength-config-value`.
 * @returns Parsed config or null.
 */
export function parseConfig(raw: string | undefined): PasswordStrengthConfig | null {
  if (!raw) return null;
  try {
    return JSON.parse(raw) as PasswordStrengthConfig;
  } catch {
    return null;
  }
}

/**
 * Initialize live feedback and generator for one password-strength field.
 *
 * @param root Input element or field wrapper.
 */
export function initPasswordStrengthContainer(root: HTMLElement): void {
  const field =
    root.closest<HTMLElement>('[data-password-strength-field]') ??
    root.parentElement?.closest<HTMLElement>('[data-password-strength-field]') ??
    root;

  const input =
    root instanceof HTMLInputElement
      ? root
      : field.querySelector<HTMLInputElement>(
          'input[data-password-strength-config-value], input.password-strength-widget, .form-password-toggle input',
        );
  if (!input) return;

  if (input.getAttribute('data-password-strength-enhanced') === '1') {
    return;
  }

  const rawConfig =
    input.getAttribute('data-password-strength-config-value') ??
    root.getAttribute('data-password-strength-config-value') ??
    undefined;
  const config = parseConfig(rawConfig);
  if (!config) return;

  const labelMap = readLabelMap(input);
  const labels: PasswordStrengthLabels = { ...DEFAULT_LABELS, ...labelMap, ...config.labels };
  config.labels = labels;

  const generatorMode =
    (input.getAttribute('data-password-strength-generator-mode') as GeneratorMode | null) ??
    config.generatorMode ??
    'off';
  config.generatorMode = generatorMode;
  config.generatorCount = Number(
    input.getAttribute('data-password-strength-generator-count') ?? config.generatorCount ?? 3,
  );

  const classes = FRAMEWORK_CLASSES[config.uiFramework] ?? FRAMEWORK_CLASSES.default;

  let feedbackHost =
    field.querySelector<HTMLElement>('[data-password-strength-feedback]') ??
    root.parentElement?.querySelector<HTMLElement>('[data-password-strength-feedback]');

  const applyFeedback = (): void => {
    if (!feedbackHost) return;
    const result = evaluatePassword(input.value, config.conditions);
    input.setAttribute('pattern', result.pattern);

    if (!config.showRequirements) {
      feedbackHost.hidden = true;
      return;
    }

    feedbackHost.hidden = false;
    renderRequirements(feedbackHost, result.requirements, labels, classes);

    const unmet = result.requirements.filter((r) => !r.met);
    if (config.feedbackPosition === 'above' && unmet.length > 0) {
      addClasses(feedbackHost, classes.alert);
    } else {
      removeClasses(feedbackHost, classes.alert);
    }
  };

  if (config.liveFeedback || config.showRequirements) {
    if (!feedbackHost) {
      feedbackHost = createFeedbackHost(input, config, classes);
    }
    input.addEventListener('input', applyFeedback);
    input.addEventListener('change', applyFeedback);
    applyFeedback();
  }

  const generateBtn = field.querySelector<HTMLButtonElement>('[data-password-strength-generate]');
  if (generateBtn && generatorMode !== 'off') {
    generateBtn.addEventListener('click', () => {
      if (generatorMode === 'modal') {
        openGeneratorModal(input, config, classes, applyFeedback);
      } else {
        applyPasswordToInput(input, generatePassword(config.conditions), labels, applyFeedback);
      }
    });
  }

  input.setAttribute('data-password-strength-enhanced', '1');
  log.debug('initialized', { id: input.id, generatorMode });
}

/**
 * Discover and initialize all password-strength widgets.
 */
export function runInit(): void {
  document
    .querySelectorAll<HTMLElement>(
      '[data-password-strength-field], [data-controller*="password-strength"], input.password-strength-widget',
    )
    .forEach((el) => initPasswordStrengthContainer(el));
}

/**
 * Initialize existing widgets and observe DOM for dynamically added fields.
 */
export function runInitAndObserve(): void {
  injectBaseStyles();
  runInit();
  const observer = new MutationObserver((mutations) => {
    for (const mutation of mutations) {
      mutation.addedNodes.forEach((node) => {
        if (!(node instanceof HTMLElement)) return;
        if (
          node.matches('[data-password-strength-field], [data-controller*="password-strength"]') ||
          node.querySelector('[data-password-strength-field], [data-controller*="password-strength"]')
        ) {
          initPasswordStrengthContainer(node);
        }
        node
          .querySelectorAll<HTMLElement>('[data-password-strength-field], [data-controller*="password-strength"]')
          .forEach(initPasswordStrengthContainer);
      });
    }
  });
  observer.observe(document.body, { childList: true, subtree: true });
}

/**
 * Apply generated password to the input (visible text).
 *
 * @param input Target password field.
 * @param password Generated value.
 * @param labels UI labels.
 * @param onApplied Callback after value is set.
 */
function applyPasswordToInput(
  input: HTMLInputElement,
  password: string,
  labels: PasswordStrengthLabels,
  onApplied: () => void,
): void {
  if (!password) {
    window.alert(labels['generator.failed'] ?? 'Could not generate password');
    return;
  }

  input.value = password;
  revealInputWithToggle(input);
  input.dispatchEvent(new Event('input', { bubbles: true }));
  input.dispatchEvent(new Event('change', { bubbles: true }));
  onApplied();
}

/**
 * Show password in the field and sync PasswordToggleBundle button state (icons + aria).
 *
 * @param input Password input inside `.form-password-toggle` when toggle is enabled.
 */
function revealInputWithToggle(input: HTMLInputElement): void {
  input.type = 'text';

  if (input.getAttribute('data-password-strength-toggle') !== '1') {
    return;
  }

  const toggleBtn = input.nextElementSibling;
  if (!(toggleBtn instanceof HTMLElement)) {
    return;
  }

  toggleBtn.querySelectorAll<HTMLElement>('.icon-hidden').forEach((el) => {
    el.style.display = 'none';
  });
  toggleBtn.querySelectorAll<HTMLElement>('.icon-visible').forEach((el) => {
    el.style.display = '';
  });

  const hiddenLabel = input.getAttribute('data-password-strength-toggle-hidden-label');
  if (hiddenLabel) {
    toggleBtn.setAttribute('aria-label', hiddenLabel);
  }
}

/**
 * Copy text to clipboard.
 *
 * @param text Value to copy.
 * @returns Whether copy succeeded.
 */
async function copyToClipboard(text: string): Promise<boolean> {
  try {
    await navigator.clipboard.writeText(text);
    return true;
  } catch {
    const area = document.createElement('textarea');
    area.value = text;
    area.style.position = 'fixed';
    area.style.left = '-9999px';
    document.body.appendChild(area);
    area.select();
    const ok = document.execCommand('copy');
    area.remove();
    return ok;
  }
}

/**
 * Open modal with password suggestions.
 *
 * @param input Target field.
 * @param config Widget config.
 * @param classes Framework classes.
 * @param onApplied Feedback refresh callback.
 */
function openGeneratorModal(
  input: HTMLInputElement,
  config: PasswordStrengthConfig,
  classes: (typeof FRAMEWORK_CLASSES)['default'],
  onApplied: () => void,
): void {
  injectBaseStyles();
  const labels = config.labels;
  const passwords = generatePasswords(config.conditions, config.generatorCount);

  const overlay = document.createElement('div');
  overlay.className = 'password-strength-modal-overlay';
  overlay.setAttribute('role', 'dialog');
  overlay.setAttribute('aria-modal', 'true');
  overlay.setAttribute('aria-labelledby', 'password-strength-modal-title');

  const modal = document.createElement('div');
  modal.className = 'password-strength-modal';

  const title = document.createElement('h2');
  title.id = 'password-strength-modal-title';
  title.textContent = labels['generator.modal_title'] ?? 'Suggested passwords';

  const hint = document.createElement('p');
  hint.className = 'password-strength-modal-hint';
  hint.textContent = labels['generator.modal_hint'] ?? '';

  const list = document.createElement('ul');
  list.className = 'password-strength-modal-list';

  const close = (): void => overlay.remove();

  overlay.addEventListener('click', (event) => {
    if (event.target === overlay) close();
  });

  if (passwords.length === 0) {
    const fail = document.createElement('p');
    fail.textContent = labels['generator.failed'] ?? 'Generation failed';
    modal.append(title, hint, fail);
  } else {
    for (const pwd of passwords) {
      const li = document.createElement('li');
      li.className = 'password-strength-modal-item';

      const code = document.createElement('code');
      code.textContent = pwd;

      const copyBtn = document.createElement('button');
      copyBtn.type = 'button';
      copyBtn.className = classes.btnSecondary;
      copyBtn.textContent = labels['generator.copy'] ?? 'Copy';
      copyBtn.addEventListener('click', async () => {
        const ok = await copyToClipboard(pwd);
        copyBtn.textContent = ok
          ? (labels['generator.copied'] ?? 'Copied!')
          : (labels['generator.copy'] ?? 'Copy');
        window.setTimeout(() => {
          copyBtn.textContent = labels['generator.copy'] ?? 'Copy';
        }, 2000);
      });

      const useBtn = document.createElement('button');
      useBtn.type = 'button';
      useBtn.className = classes.btnPrimary;
      useBtn.textContent = labels['generator.use_password'] ?? 'Use';
      useBtn.addEventListener('click', () => {
        applyPasswordToInput(input, pwd, labels, onApplied);
        close();
      });

      li.append(code, copyBtn, useBtn);
      list.appendChild(li);
    }
    modal.append(title, hint, list);
  }

  const actions = document.createElement('div');
  actions.className = 'password-strength-modal-actions';
  const closeBtn = document.createElement('button');
  closeBtn.type = 'button';
  closeBtn.className = classes.btnSecondary;
  closeBtn.textContent = labels['generator.close'] ?? 'Close';
  closeBtn.addEventListener('click', close);
  actions.appendChild(closeBtn);
  modal.appendChild(actions);

  overlay.appendChild(modal);
  document.body.appendChild(overlay);
  closeBtn.focus();
}

function readLabelMap(root: HTMLElement): Record<string, string> {
  const raw = root.getAttribute('data-password-strength-labels-value');
  if (!raw) return {};
  try {
    return JSON.parse(raw) as Record<string, string>;
  } catch {
    return {};
  }
}

function createFeedbackHost(
  input: HTMLInputElement,
  config: PasswordStrengthConfig,
  classes: (typeof FRAMEWORK_CLASSES)['default'],
): HTMLElement {
  const host = document.createElement('div');
  host.setAttribute('data-password-strength-feedback', '1');
  host.className = classes.wrapper;
  host.setAttribute('aria-live', 'polite');

  const row = input.closest('.password-strength-input-row');
  if (config.feedbackPosition === 'above') {
    row?.parentElement?.insertBefore(host, row);
  } else {
    row?.insertAdjacentElement('afterend', host);
  }
  return host;
}

function renderRequirements(
  host: HTMLElement,
  requirements: ReturnType<typeof evaluatePassword>['requirements'],
  labels: Record<string, string>,
  classes: (typeof FRAMEWORK_CLASSES)['default'],
): void {
  const list = document.createElement('ul');
  list.className = classes.list;

  for (const req of requirements) {
    const li = document.createElement('li');
    li.className = req.met ? classes.itemMet : classes.itemUnmet;
    li.textContent = formatRequirementLabel(req.labelKey, req.required, labels);
    li.setAttribute('data-requirement-id', req.id);
    list.appendChild(li);
  }

  host.replaceChildren(list);
}

function splitClassTokens(classString: string): string[] {
  return classString.trim().split(/\s+/).filter(Boolean);
}

function addClasses(element: HTMLElement, classString: string): void {
  for (const token of splitClassTokens(classString)) {
    element.classList.add(token);
  }
}

function removeClasses(element: HTMLElement, classString: string): void {
  for (const token of splitClassTokens(classString)) {
    element.classList.remove(token);
  }
}

function injectBaseStyles(): void {
  if (stylesInjected || document.getElementById('password-strength-inline-styles')) {
    stylesInjected = true;
    return;
  }
  const style = document.createElement('style');
  style.id = 'password-strength-inline-styles';
  style.textContent = `
    .password-strength-input-row{display:flex;gap:.5rem;align-items:stretch;width:100%}
    .password-strength-input-grow{flex:1 1 auto;min-width:0}
    .password-strength-input-row--toggle .input-group{width:100%}
    .password-strength-input-row .password-strength-widget,.password-strength-input-row input{flex:1 1 auto;min-width:0}
    .password-strength-generate-btn{flex:0 0 auto;white-space:nowrap}
    .password-strength-modal-overlay{position:fixed;inset:0;z-index:1050;display:flex;align-items:center;justify-content:center;background:rgba(0,0,0,.45);padding:1rem}
    .password-strength-modal{background:#fff;border-radius:.5rem;max-width:32rem;width:100%;box-shadow:0 .5rem 1rem rgba(0,0,0,.15);padding:1.25rem}
    .password-strength-modal h2{font-size:1.125rem;margin:0 0 .5rem}
    .password-strength-modal-hint{margin:0 0 .5rem;color:#6c757d;font-size:.9rem}
    .password-strength-modal-list{list-style:none;margin:1rem 0;padding:0}
    .password-strength-modal-item{display:flex;gap:.5rem;align-items:center;margin-bottom:.5rem;flex-wrap:wrap}
    .password-strength-modal-item code{flex:1 1 12rem;word-break:break-all;padding:.35rem .5rem;background:#f8f9fa;border-radius:.25rem;font-size:.95rem}
    .password-strength-modal-actions{display:flex;gap:.5rem;justify-content:flex-end;flex-wrap:wrap;margin-top:1rem}
  `;
  document.head.appendChild(style);
  stylesInjected = true;
}

if (typeof window !== 'undefined') {
  (window as unknown as { NowoPasswordStrength?: object }).NowoPasswordStrength = {
    initPasswordStrengthContainer,
    runInit,
    runInitAndObserve,
    evaluatePassword,
    generatePassword,
    generatePasswords,
  };
}

log.scriptLoaded();

if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', runInitAndObserve);
} else {
  runInitAndObserve();
}
