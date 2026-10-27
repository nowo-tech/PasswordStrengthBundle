# Security


## Table of contents

- [Attack surface](#attack-surface)
- [Threats and mitigations](#threats-and-mitigations)
- [Client vs server validation](#client-vs-server-validation)
- [Recommended host setup](#recommended-host-setup)
- [Password generator](#password-generator)
- [Dependencies](#dependencies)
- [Reporting](#reporting)
- [Release security checklist (12.4.1)](#release-security-checklist-1241)
- [AI security audit](#ai-security-audit)

## Attack surface

| Input | Description |
|-------|-------------|
| **Form options** | `policy_mode`, `level`, `conditions`, `enforce`, generator settings on `PasswordStrengthType`. |
| **Bundle configuration** | `nowo_password_strength` YAML (`profiles` (formerly `levels`), themes, toggle integration). |
| **HTTP form fields** | Password values submitted by end users (handled by the application, not stored by this bundle). |
| **Client script** | `password-strength.js` reads data attributes and updates the DOM (requirements list, generator). |
| **Translations** | `NowoPasswordStrengthBundle` domain strings rendered in Twig. |

## Threats and mitigations

| Threat | Risk | Mitigation |
|--------|------|------------|
| **Bypass client checks** | Attacker disables JS or edits DOM; weak passwords accepted in browser only. | `PasswordStrengthType` attaches the **`PasswordStrength` constraint** for the same policy (`enforce: true`, default), so the submit is validated server-side; HTML `pattern` and live feedback are UX only. |
| **Oversized input** | Very long values make every rule (and custom `regex`) run on megabytes. | Hard cap of **4096 bytes** checked first: longer values are a `max_length` violation and no regex runs (PHP and TypeScript). |
| **XSS via Twig** | Malicious override templates or translated strings inject script. | Use Symfony Twig auto-escaping; override templates with care; do not pass raw HTML in requirement labels without escaping. |
| **CSRF** | Password change forms without CSRF protection. | Application responsibility — use Symfony Form CSRF (enabled by default in full-stack apps). |
| **Password logging** | Passwords written to logs or profiler. | Bundle does not log password values; avoid logging form data in application code. |
| **Weak policy configuration** | Overly weak or silently empty policies. | A profile or inline `conditions` with no rule, an unknown condition key, an unknown `level` or a missing `default_profile` fail loudly (container compile, invalid form option, `ConstraintDefinitionException`) instead of accepting every password. A profile named like a built-in one is merged onto it. |
| **Dependency vulnerabilities** | Outdated Symfony/PHP. | Run `composer audit` before releases; Dependabot enabled on the repository. |

## Client vs server validation

- **Live feedback** and HTML `pattern` are UX hints only. They can be bypassed in the browser.
- **Always** use the `PasswordStrength` validator (or equivalent server checks) on submit.
- The bundle does **not** store, log, or transmit passwords to third parties beyond normal form handling in your application.
- The policy is **visible client-side**: the conditions (including `not_contain` fragments and custom `regex`) are serialized into the widget's `data-password-strength-config-value` attribute. Never put secrets (internal hostnames, other users' data, API keys) in `not_contain` or `regex`.

## Recommended host setup

- Keep `enforce: true` (default) on `PasswordStrengthType`, or put `#[PasswordStrength]` on the entity property — not both, or each failure is reported twice. When the form uses custom `validation_groups`, the attached constraint (group `Default`) only runs if those groups include `Default`; otherwise set `enforce: false` and add `new PasswordStrength(..., groups: [...])` to `constraints` yourself.
- Add **`NotBlank`** when the password is required: like Symfony's validators, `PasswordStrength` accepts an empty value.
- Add **`NotCompromisedPassword`** (Have I Been Pwned k-anonymity check) for account passwords: composition rules do not catch passwords that leaked elsewhere.
- Prefer length over composition: a `min_length` of 12+ (built-in `strong`) is a better default than many `require_*` rules.

## Password generator

Generated passwords are created in the browser (`crypto.getRandomValues` / Web Crypto). They are not sent to third parties by this bundle. Generated values are only placed in the form field the user controls.

## Dependencies

Keep Symfony, PHP, and optional [PasswordToggleBundle](https://github.com/nowo-tech/PasswordToggleBundle) updated.

Public disclosure process: [.github/SECURITY.md](../.github/SECURITY.md).

## Reporting

Do not report vulnerabilities via public GitHub issues. Email **hectorfranco@nowo.tech** (see `.github/SECURITY.md`).

## Release security checklist (12.4.1)

Before tagging a release, confirm:

| Item | Notes |
|------|--------|
| **SECURITY.md** | This document is current and linked from the README. |
| **`.gitignore` / `.env`** | No committed secrets; demos use `.env.example`. |
| **Recipe / Flex** | `.symfony/recipes` ships only safe defaults. |
| **Input / output** | Validator mirrors policy; Twig escapes translated output. |
| **Dependencies** | `composer audit` run in CI / locally. |
| **Logging** | No password values in bundle logs. |
| **Cryptography** | Generator uses secure random APIs in the browser only. |
| **REQ-SEC-004 (AI audit)** | **Pass (good)** — risk **Low**, re-audit 2026-10-27 (v2.9.0, fixes verified for 2.10.0); see [AI security audit](#ai-security-audit) and monorepo `BUNDLES_SECURITY_ANALYSIS.md` (Appendix AI). |

Record confirmation in the release PR or tag notes.

## AI security audit

| Field | Value |
| ----- | ----- |
| Latest audit | 2026-10-27 (re-audit of v2.9.0; fixes verified in PR #66, head `001f917`, released as 2.10.0) |
| Method | Independent Claude Code security review (static: `src/`, configuration tree, form type, validator, Twig themes, TypeScript, security docs) |
| Grade | **Pass (good)** |
| Risk | Low |
| Record | `BUNDLES_SECURITY_ANALYSIS.md` (Nowo bundles monorepo), REQ-SEC-004 table, Appendix AI |
| Residual | The policy is client-visible by design (no secrets in `not_contain` / `regex`); with custom `validation_groups` the attached constraint needs the `Default` group (see [Recommended host setup](#recommended-host-setup)); breached-password checks are the host's job (`NotCompromisedPassword`); a custom `regex` from configuration is trusted (no ReDoS analysis, bounded by the 4096-byte cap); the HTML `pattern` uses escapes that need the browser's `v` flag — older browsers (Chrome < 112, Firefox < 116, Safari < 17) ignore it, the script and the server still enforce |

### Findings (2026-10-27, all fixed in 2.10.0, PR #66)

| Id | Severity | Finding | Status |
| -- | -------- | ------- | ------ |
| L1 | Low | `PasswordStrengthType` never attached the `PasswordStrength` constraint: the policy was only enforced when the host added it | Fixed — `enforce` option (default `true`) attaches the constraint built from `policy_mode` / `level` / `conditions` |
| L2 | Low | Silent weakening: unknown `level` fell back to the default profile, `conditions` mode without conditions / unknown keys / no rule accepted everything, rule-less profiles allowed, a profile named like a built-in replaced it | Fixed — unknown level and invalid inline conditions throw (`InvalidOptionsException` / `ConstraintDefinitionException`), rule-less profiles fail at compile time, built-in-named profiles are merged |
| L3 | Low | No input length cap before the rules and regexes run; unique-character count split the whole value | Fixed — 4096-byte cap reported as `max_length` (PHP + TS); early-exit unique-character count |
| L4 | Low | TypeScript escaping omitted `-` (the default special set formed the range `+`..`=`, so digits counted as special characters) and `/` | Fixed — `escapeForCharClass()` / `escapeLiteral()` shared with PHP, checked by the shared vectors `tests/Fixtures/password-vectors.json` (PHPUnit + Vitest), patterns valid under the browser's `v` flag |
| L5 | Low | Outdated security docs (supported versions 1.x, `levels`, grade) | Fixed — this section, `.github/SECURITY.md` supported versions |
| I1 | Info | `trim` defaulted to `true` (Symfony `PasswordType`: `false`) | Fixed — default `false` |
| I2 | Info | Policy visible client-side | Documented ([Client vs server validation](#client-vs-server-validation)) |
| I3 | Info | Generator produced 8-character passwords for short policies | Fixed — at least 16 characters unless `max_length` is lower |
| I4 | Info | No guidance on `NotBlank` / `NotCompromisedPassword` | Documented ([Recommended host setup](#recommended-host-setup)) |
