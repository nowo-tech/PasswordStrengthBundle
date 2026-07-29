# Security


## Table of contents

- [Attack surface](#attack-surface)
- [Threats and mitigations](#threats-and-mitigations)
- [Client vs server validation](#client-vs-server-validation)
- [Password generator](#password-generator)
- [Dependencies](#dependencies)
- [Reporting](#reporting)
- [Release security checklist (12.4.1)](#release-security-checklist-1241)

## Attack surface

| Input | Description |
|-------|-------------|
| **Form options** | `policy_mode`, `level`, `conditions`, generator settings on `PasswordStrengthType`. |
| **Bundle configuration** | `nowo_password_strength` YAML (levels, themes, toggle integration). |
| **HTTP form fields** | Password values submitted by end users (handled by the application, not stored by this bundle). |
| **Client script** | `password-strength.js` reads data attributes and updates the DOM (requirements list, generator). |
| **Translations** | `NowoPasswordStrengthBundle` domain strings rendered in Twig. |

## Threats and mitigations

| Threat | Risk | Mitigation |
|--------|------|------------|
| **Bypass client checks** | Attacker disables JS or edits DOM; weak passwords accepted in browser only. | **`PasswordStrength` validator** enforces policy server-side; document that HTML `pattern` and live feedback are UX only. |
| **XSS via Twig** | Malicious override templates or translated strings inject script. | Use Symfony Twig auto-escaping; override templates with care; do not pass raw HTML in requirement labels without escaping. |
| **CSRF** | Password change forms without CSRF protection. | Application responsibility — use Symfony Form CSRF (enabled by default in full-stack apps). |
| **Password logging** | Passwords written to logs or profiler. | Bundle does not log password values; avoid logging form data in application code. |
| **Weak policy configuration** | Overly weak `levels` in YAML. | Document recommended presets; integrators choose policy per use case. |
| **Dependency vulnerabilities** | Outdated Symfony/PHP. | Run `composer audit` before releases; Dependabot enabled on the repository. |

## Client vs server validation

- **Live feedback** and HTML `pattern` are UX hints only. They can be bypassed in the browser.
- **Always** use the `PasswordStrength` validator (or equivalent server checks) on submit.
- The bundle does **not** store, log, or transmit passwords to third parties beyond normal form handling in your application.

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
| **REQ-SEC-004 (AI audit)** | Pass (conditional) — Low residual (server validator authoritative; do not log passwords); see monorepo `BUNDLES_SECURITY_ANALYSIS.md` (audit 2026-07-29). |

Record confirmation in the release PR or tag notes.
