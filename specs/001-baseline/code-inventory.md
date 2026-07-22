# Code inventory — 100% traceability

**Baseline spec**: [`spec.md`](spec.md)  
**Package**: `nowo-tech/password-strength-bundle`  
**Last audited**: 2026-07-07

This file proves that **every source artifact** under `src/` is referenced by the baseline specification. Co-located Vitest files (`*.test.ts`) enforce frontend contracts; PHPUnit covers PHP under `tests/`.

## PHP classes (`src/**/*.php`)

| Source file | Spec section | Requirement IDs |
| --- | --- | --- |
| `PasswordStrengthBundle.php` | Bundle entry | FR-BUNDLE-001 |
| `DependencyInjection/Configuration.php` | Config tree | FR-CFG-001 |
| `DependencyInjection/PasswordStrengthExtension.php` | DI extension | FR-CFG-002 |
| `DependencyInjection/Compiler/TwigPathsPass.php` | Twig namespace | FR-TWIG-001 |
| `Form/PasswordStrengthType.php` | Form type | FR-FORM-001 |
| `Integration/ParentFormTypeResolver.php` | Parent type resolution | FR-INT-001 |
| `Integration/PasswordToggleIntegration.php` | Toggle bundle bridge | FR-INT-002 |
| `Model/EvaluationResult.php` | Evaluator result DTO | FR-MODEL-001 |
| `Model/FeedbackPosition.php` | above/below enum | FR-MODEL-002 |
| `Model/GeneratorMode.php` | off/input/modal enum | FR-MODEL-003 |
| `Model/PasswordConditions.php` | Policy conditions DTO | FR-MODEL-004 |
| `Model/PolicyMode.php` | level/conditions enum | FR-MODEL-005 |
| `Model/RequirementResult.php` | Single requirement state | FR-MODEL-006 |
| `Service/PolicyResolver.php` | Policy resolution | FR-POL-001 |
| `Service/PasswordStrengthEvaluator.php` | Server-side evaluation | FR-EVAL-001 |
| `Service/PasswordPatternBuilder.php` | HTML pattern builder | FR-PAT-001 |
| `Validator/PasswordStrength.php` | Constraint | FR-VAL-001 |
| `Validator/PasswordStrengthValidator.php` | Server validation | FR-VAL-002 |

## TypeScript production (`src/Resources/assets/src/`)

| Source file | Spec section | Requirement IDs |
| --- | --- | --- |
| `index.ts` | IIFE bootstrap | FR-UI-001 |
| `password-strength.ts` | DOM widget & generator UI | FR-UI-002 |
| `password-strength-lib.ts` | Evaluation & labels | FR-UI-003 |
| `password-generator.ts` | Compliant password generation | FR-GEN-001 |
| `password-pattern-builder.ts` | Client pattern (mirror PHP) | FR-PAT-002 |
| `types.ts` | Shared TS interfaces | FR-UI-004 |
| `logger.ts` | Debug logging | FR-UI-005 |

## Vitest co-located (`src/Resources/assets/src/*.test.ts`)

| Source file | Spec section | Requirement IDs |
| --- | --- | --- |
| `logger.test.ts` | Logger contract tests | FR-UI-005 |
| `password-generator.test.ts` | Generator tests | FR-GEN-001 |
| `password-strength-dom.test.ts` | DOM widget tests | FR-UI-002 |
| `password-strength-lib.test.ts` | Evaluator tests | FR-UI-003 |

## Legacy JavaScript (`src/Resources/public/`)

| Source file | Spec section | Requirement IDs |
| --- | --- | --- |
| `Resources/public/password-strength.js` | Pre-built IIFE fallback | FR-LEGACY-001 |

## Symfony config (`src/Resources/config/`)

| Source file | Spec section | Requirement IDs |
| --- | --- | --- |
| `Resources/config/services.yaml` | Service wiring | FR-DI-001 |

## Twig form themes (`src/Resources/views/Form/`)

| Source file | Spec section | Requirement IDs |
| --- | --- | --- |
| `password_strength_theme.html.twig` | Default div layout | FR-TWIG-002 |
| `password_strength_theme_bootstrap3.html.twig` | Bootstrap 3 | FR-TWIG-003 |
| `password_strength_theme_bootstrap3_horizontal.html.twig` | Bootstrap 3 horizontal | FR-TWIG-003 |
| `password_strength_theme_bootstrap4.html.twig` | Bootstrap 4 | FR-TWIG-003 |
| `password_strength_theme_bootstrap4_horizontal.html.twig` | Bootstrap 4 horizontal | FR-TWIG-003 |
| `password_strength_theme_bootstrap5.html.twig` | Bootstrap 5 | FR-TWIG-003 |
| `password_strength_theme_bootstrap5_horizontal.html.twig` | Bootstrap 5 horizontal | FR-TWIG-003 |
| `password_strength_theme_foundation5.html.twig` | Foundation 5 | FR-TWIG-004 |
| `password_strength_theme_foundation6.html.twig` | Foundation 6 | FR-TWIG-004 |
| `password_strength_theme_table.html.twig` | Table layout | FR-TWIG-005 |
| `password_strength_theme_tailwind2.html.twig` | Tailwind 2 | FR-TWIG-006 |

## Translations (`src/Resources/translations/`)

| Source file | Spec section | Requirement IDs |
| --- | --- | --- |
| `NowoPasswordStrengthBundle.en.yaml` | i18n | FR-I18N-001 |
| `NowoPasswordStrengthBundle.es.yaml` | i18n | FR-I18N-001 |
| `NowoPasswordStrengthBundle.de.yaml` | i18n | FR-I18N-001 |
| `NowoPasswordStrengthBundle.fr.yaml` | i18n | FR-I18N-001 |
| `NowoPasswordStrengthBundle.it.yaml` | i18n | FR-I18N-001 |
| `NowoPasswordStrengthBundle.nl.yaml` | i18n | FR-I18N-001 |
| `NowoPasswordStrengthBundle.pt.yaml` | i18n | FR-I18N-001 |

## Coverage summary

| Category | Files | Mapped |
| --- | ---: | ---: |
| PHP classes | 18 | 18 |
| TypeScript production | 7 | 7 |
| Vitest co-located | 4 | 4 |
| Legacy JS | 1 | 1 |
| Symfony config | 1 | 1 |
| Twig themes | 11 | 11 |
| Translations | 7 | 7 |
| **Total sources under `src/`** | **49** | **49** |
