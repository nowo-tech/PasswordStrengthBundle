# Spec-driven development

Password Strength Bundle follows the Nowo bundle conventions documented in the monorepo (`BUNDLES_FULL_SPECS_DETAILS.md`).

## Scope

| Area | Implementation |
|------|----------------|
| Form type | `PasswordStrengthType` extends parent `PasswordType` (Symfony or PasswordToggle) |
| Validation | `PasswordStrength` constraint + `PasswordStrengthValidator` |
| Policy | `PasswordPolicyEvaluator`, `PasswordPatternBuilder` |
| Frontend | `password-strength.ts` (Vite IIFE), data attributes on widget |
| Themes | Twig blocks per framework layout |
| Config | `nowo_password_strength` extension |

## REQ mapping (high level)

- **REQ-PS-001** — Policy modes `level` and `conditions`
- **REQ-PS-002** — Named levels from bundle configuration
- **REQ-PS-003** — Server-side validator mirrors form policy
- **REQ-PS-004** — Live feedback without full page reload
- **REQ-PS-005** — Optional password generator
- **REQ-PS-006** — Multi-framework Twig themes
- **REQ-PS-007** — Optional PasswordToggleBundle integration

Detailed acceptance criteria live in the monorepo specs checklist for this bundle.

## Demo

Symfony 8 demo validates end-to-end behaviour; see [DEMO-FRANKENPHP.md](DEMO-FRANKENPHP.md).
