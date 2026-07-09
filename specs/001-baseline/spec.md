# Feature Specification: PasswordStrengthBundle baseline (100% code coverage)

**Feature Branch**: `001-baseline`  
**Created**: 2026-07-07  
**Status**: Active  
**Input**: Backfill GitHub Spec Kit baseline documenting 100% of production code in `src/`.

**Related docs**: [`docs/SPEC-DRIVEN-DEVELOPMENT.md`](../../docs/SPEC-DRIVEN-DEVELOPMENT.md), [`docs/CONFIGURATION.md`](../../docs/CONFIGURATION.md), [`docs/USAGE.md`](../../docs/USAGE.md)  
**Code inventory (traceability)**: [`code-inventory.md`](code-inventory.md)

---

## Summary

**Package**: `nowo-tech/password-strength-bundle`  
**Configuration root**: `nowo_password_strength`

Symfony `PasswordStrengthType` form field with configurable strength policies (preset levels or inline conditions), live browser feedback, HTML `pattern`, optional password generator, server-side validator, and optional PasswordToggleBundle integration.

---

## User Scenarios & Testing

### User Story 1 — Live strength feedback (Priority: P1)

As a form author, I use `PasswordStrengthType` so users see which requirements are met while typing.

**Independent Test**: Render form with `live_feedback=true` → requirement list updates on input; `pattern` attribute matches PHP-built regex.

**Acceptance Scenarios**:

1. **Given** `policy_mode=level` and `level=medium`, **When** field renders, **Then** `PolicyResolver` applies preset from `levels` config and `PasswordPatternBuilder` sets HTML pattern.
2. **Given** `policy_mode=conditions`, **When** inline conditions provided, **Then** they override level presets for that field only.
3. **Given** JavaScript disabled, **When** form submits invalid password, **Then** `PasswordStrengthValidator` rejects with `{{ missing }}` parameter.

---

### User Story 2 — Preset policy levels (Priority: P1)

As an integrator, I configure weak/medium/strong defaults in YAML and pick a level per field.

**Acceptance Scenarios**:

1. **Given** `default_level=medium` in config, **When** field omits `level` option, **Then** medium preset applies.
2. **Given** custom `levels` map in YAML, **When** extension loads, **Then** merged levels replace bundle defaults.
3. **Given** password meets all conditions, **When** evaluator runs, **Then** `EvaluationResult::valid` is true with empty `missing`.

---

### User Story 3 — Password generator (Priority: P2)

As an integrator, I enable generator modes so users can fill or pick compliant passwords.

**Acceptance Scenarios**:

1. **Given** `generator_mode=input`, **When** user clicks generate, **Then** `password-generator.ts` fills input with policy-compliant string.
2. **Given** `generator_mode=modal`, **When** opened, **Then** up to `generator_count` suggestions display with copy action.
3. **Given** `generator_mode=off`, **When** page loads, **Then** no generator UI rendered.

---

### User Story 4 — PasswordToggle integration (Priority: P2)

As an integrator with PasswordToggleBundle installed, I want show/hide on strength fields without manual parent type wiring.

**Acceptance Scenarios**:

1. **Given** `use_password_toggle=true` and toggle bundle present, **When** form builds, **Then** `ParentFormTypeResolver` selects toggle `PasswordType` as parent.
2. **Given** explicit `parent_form_type`, **When** set, **Then** auto-detection is skipped.
3. **Given** toggle parent active, **When** theme renders, **Then** `password_toggle_parent` var enables combined widget markup.

---

### User Story 5 — Multi-framework Twig themes (Priority: P3)

As an integrator, I select a form theme matching Bootstrap, Foundation, Tailwind, or table layouts.

**Acceptance Scenarios**:

1. **Given** `form_theme` in bundle config matches app `twig.form_themes`, **When** field renders, **Then** correct theme block provides feedback placement (`above`/`below`).
2. **Given** `ui_framework` field option, **When** TS initializes, **Then** CSS class map in `password-strength.ts` applies framework-specific styling.

---

### Edge Cases

- Empty/null password: validator skips (optional field semantics).
- Non-string value in validator: `UnexpectedValueException`.
- `generator_count` clamped to 1–10 in form view and config.
- Legacy `password-strength.js` available when build pipeline not run (reduced update path).

---

## Requirements

### Bundle & DI

- **FR-BUNDLE-001**: `PasswordStrengthBundle` MUST register `TwigPathsPass` and expose alias `nowo_password_strength`.
- **FR-DI-001**: `services.yaml` MUST wire form type, resolver, evaluator, pattern builder, validator, and integration services.
- **FR-CFG-001**: `Configuration` MUST define: `form_theme`, `feedback_position`, `show_requirements`, `live_feedback`, `default_level`, `generator_mode`, `generator_count`, `use_password_toggle`, `parent_form_type`, `levels`.
- **FR-CFG-002**: Extension MUST load services, set parameters from merged config, and register Twig path namespace.
- **FR-TWIG-001**: `TwigPathsPass` MUST add `Resources/views` under bundle namespace for form theme overrides.

### Form type

- **FR-FORM-001**: `PasswordStrengthType` MUST resolve policy via `PolicyResolver`, expose pattern/conditions/generator vars to Twig, extend resolved parent type (Symfony or toggle PasswordType).
- **FR-INT-001**: `ParentFormTypeResolver` MUST choose parent FQCN from config, toggle availability, and `use_password_toggle` flag.
- **FR-INT-002**: `PasswordToggleIntegration` MUST detect toggle form type class and expose helper for Twig/TS.

### Policy engine (PHP)

- **FR-POL-001**: `PolicyResolver` MUST merge `PolicyMode::Level` presets or `PolicyMode::Conditions` inline rules into `PasswordConditions`.
- **FR-EVAL-001**: `PasswordStrengthEvaluator` MUST evaluate min length, character classes, and return `EvaluationResult` with `missing` labels.
- **FR-PAT-001**: `PasswordPatternBuilder` (PHP) MUST emit HTML5-compatible pattern string from conditions.
- **FR-MODEL-001**–**FR-MODEL-006**: Model enums/DTOs (`EvaluationResult`, `FeedbackPosition`, `GeneratorMode`, `PasswordConditions`, `PolicyMode`, `RequirementResult`) MUST type policy state shared by PHP and TS.

### Validation

- **FR-VAL-001**: `PasswordStrength` constraint MUST carry `policyMode`, `level`, `conditions`, `message`.
- **FR-VAL-002**: `PasswordStrengthValidator` MUST reuse resolver + evaluator and build violation with missing requirements list.

### Frontend (TypeScript)

- **FR-UI-001**: `index.ts` MUST bootstrap IIFE, scan `[data-password-strength]`, and attach widgets.
- **FR-UI-002**: `password-strength.ts` MUST render live feedback, inject framework CSS, wire generator UI, and sync with input events.
- **FR-UI-003**: `password-strength-lib.ts` MUST mirror PHP evaluation rules and format requirement labels.
- **FR-UI-004**: `types.ts` MUST define config, labels, and framework types shared across modules.
- **FR-UI-005**: `logger.ts` MUST provide namespaced debug logger with test hook.
- **FR-GEN-001**: `password-generator.ts` MUST generate passwords satisfying active `PasswordConditions`.
- **FR-PAT-002**: `password-pattern-builder.ts` MUST build client-side pattern consistent with PHP builder.
- **FR-LEGACY-001**: Committed `password-strength.js` MUST remain loadable without Vite build for downstream consumers.

### Twig themes

- **FR-TWIG-002**: Base `password_strength_theme.html.twig` MUST render feedback block and hidden config JSON for JS.
- **FR-TWIG-003**: Bootstrap 3/4/5 (+ horizontal) themes MUST wrap widgets with grid-appropriate markup.
- **FR-TWIG-004**: Foundation 5/6 themes MUST align with foundation form structure.
- **FR-TWIG-005**: Table theme MUST render requirements in table layout.
- **FR-TWIG-006**: Tailwind 2 theme MUST use utility-class hooks compatible with `FRAMEWORK_CLASSES.tailwind2`.

### Internationalization

- **FR-I18N-001**: Translation YAML files MUST provide requirement labels and generator strings for shipped locales.

---

## Success Criteria

- **SC-001**: **49/49** files under `src/` mapped in [`code-inventory.md`](code-inventory.md).
- **SC-002**: Config keys match `Configuration.php` and `docs/CONFIGURATION.md`.
- **SC-003**: PHPUnit + PHPStan + Vitest pass (`make release-check`).
- **SC-004**: PHP and TS evaluators agree on valid/invalid passwords for shared fixtures.
- **SC-005**: Toggle integration degrades gracefully when PasswordToggleBundle absent.

---

## Configuration reference (normative defaults)

| Key | Default | Behavior |
| --- | --- | --- |
| `form_theme` | `form_div_layout.html.twig` | Must match app Twig form themes |
| `feedback_position` | `below` | `above` \| `below` |
| `show_requirements` | `true` | Show checklist UI |
| `live_feedback` | `true` | Update on keystroke |
| `default_level` | `medium` | Preset when field omits level |
| `generator_mode` | `off` | `off` \| `input` \| `modal` |
| `generator_count` | `3` | Modal suggestions (1–10) |
| `use_password_toggle` | `true` | Auto parent when toggle installed |
| `parent_form_type` | `null` | Explicit parent FQCN override |
| `levels.weak` | min 6 | Preset conditions |
| `levels.medium` | min 8 + classes | Preset conditions |
| `levels.strong` | min 12 + special | Preset conditions |

---

## Explicit non-goals

- Password hashing, storage, or breach-database checks.
- Account lockout or authentication policy.
- Password history / expiry (PasswordPolicyBundle).
- Demo Docker tooling as Packagist API.

---

## Validation

| Check | Command |
| --- | --- |
| Full QA | `make release-check` |
| PHP tests | `make qa` |
| TS tests | `make test-ts` |
| Code inventory | `find src -type f \| wc -l` equals inventory total |

When changing behavior, update this spec, `code-inventory.md`, tests, and integrator docs.
