# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [1.0.0] - 2026-07-03

First stable release of **Password Strength Bundle**.

### Added

- **PasswordStrengthType** — extends Symfony `PasswordType` (or [PasswordToggleBundle](https://github.com/nowo-tech/PasswordToggleBundle) `PasswordType` when installed and enabled).
- **Policy modes** — `level` (weak / medium / strong / custom) or inline `conditions`.
- **PasswordStrength** validator constraint — server-side enforcement aligned with form policies.
- **HTML `pattern`** — built automatically from the active policy (`PasswordPatternBuilder`).
- **Live feedback** — TypeScript (Vite + pnpm) evaluates requirements client-side; IIFE bundle `password-strength.js`.
- **Password generator** — modes `off`, `input` (fill field), `modal` (suggestions with copy / use).
- **Feedback position** — configurable `above` or `below` the input.
- **Multi-framework Twig form themes** — Bootstrap 3–5, Tailwind 2, Foundation 5–6, table/div layouts.
- **Optional PasswordToggleBundle integration** — auto-detected; `use_password_toggle` and `parent_form_type` configuration.
- **Translations** — `PasswordStrengthBundle` domain (EN/ES).
- **Demo** — Symfony 8 + FrankenPHP (`demo/symfony8/`) with locale-prefixed routes (`/en/`, `/es/`).
- **Development** — PHPUnit, Vitest, PHP-CS-Fixer, Rector, PHPStan, GitHub Actions CI, release workflows.

### Requirements

- PHP >= 8.1, < 8.6
- Symfony ^6.0 || ^7.0 || ^8.0
