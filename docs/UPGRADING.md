# Upgrading

This document describes how to upgrade between versions of Password Strength Bundle.


## Table of contents

- [2.1.0 (2026-07-29)](#210-2026-07-29)
- [2.0.0 (2026-07-22)](#200-2026-07-22)
- [1.0.0 (2026-07-03)](#100-2026-07-03)
- [1.1.0 (2026-07-09)](#110-2026-07-09)
- [1.2.0 (2026-07-13)](#120-2026-07-13)
- [1.3.0 (2026-07-16)](#130-2026-07-16)
- [Unreleased / 2.x](#unreleased-2x)

## 2.1.0 (2026-07-29)

No breaking changes for application consumers. Upgrade with:

```bash
composer update nowo-tech/password-strength-bundle
```

- **Runtime / API** — unchanged from 2.0.0 (Twig namespace and translation domain remain `NowoPasswordStrengthBundle`).
- **Maintainers** — `make release-check` now also runs `check-open-prs` and `coverage-check` (≥99% PHP lines). See [CONTRIBUTING.md](CONTRIBUTING.md) and [GITHUB_CI.md](GITHUB_CI.md).

## 2.0.0 (2026-07-22)

**Breaking** for apps that override Twig templates or translations. Upgrade with:

```bash
composer update nowo-tech/password-strength-bundle
```

- **Twig namespace** — Update logical template names from `@PasswordStrengthBundle/...` to `@NowoPasswordStrengthBundle/...`.
- **Application overrides** — Move `templates/bundles/PasswordStrengthBundle/...` to `templates/bundles/NowoPasswordStrengthBundle/...`.
- **Translations** — Rename application override files from `translations/PasswordStrengthBundle.{locale}.yaml` to `translations/NowoPasswordStrengthBundle.{locale}.yaml`.
- **Form option** — Default `translation_domain` is now `NowoPasswordStrengthBundle` (override per field if you still need the old domain temporarily).
- **TwigPathsPass** — Prepends the app override directory when present so application templates win over the bundle.

PHP class namespaces (`Nowo\PasswordStrengthBundle\...`), config key `nowo_password_strength`, and the asset package `nowo_password_strength` are unchanged.

## 1.0.0 (2026-07-03)

First stable release. No upgrade steps when installing for the first time.

- **PHP:** >= 8.1, < 8.6
- **Symfony:** ^6.0 || ^7.0 || ^8.0
- **Composer:** `composer require nowo-tech/password-strength-bundle`
- **Assets:** `php bin/console assets:install` and include `password-strength.js` via `asset('password-strength.js', 'nowo_password_strength')` in your layout (see [INSTALLATION.md](INSTALLATION.md)).
- **PasswordToggleBundle** is optional; when not installed the bundle uses Symfony `PasswordType` automatically.

## 1.1.0 (2026-07-09)

No breaking changes. Upgrade with:

```bash
composer update nowo-tech/password-strength-bundle
```

- **New locales** — `de`, `fr`, `it`, `nl`, and `pt` ship in `PasswordStrengthBundle` translations. They apply automatically when your app locale matches; no configuration changes required.
- **Overrides** — use `translations/PasswordStrengthBundle.{locale}.yaml` in your app to customize strings (see [INSTALLATION.md](INSTALLATION.md) for the path used in that version).
- **Spec Kit** — maintainer-only scaffolding (`specs/`, `.specify/`, `.cursor/skills/`); not required for production installs.

## 1.2.0 (2026-07-13)

No breaking changes. Upgrade with:

```bash
composer update nowo-tech/password-strength-bundle
```

- **Asset package** — the bundle now prepends a `nowo_password_strength` assets package. No configuration is required.
- **Recommended Twig snippet** — update your layout if you still use a hard-coded path:

```twig
<script src="{{ asset('password-strength.js', 'nowo_password_strength') }}" defer></script>
```

The previous `asset('bundles/passwordstrength/password-strength.js')` path continues to work after `assets:install`; the package alias is the supported approach (required for AssetMapper). See [INSTALLATION.md](INSTALLATION.md).

## 1.3.0 (2026-07-16)

No breaking changes for application consumers. Upgrade with:

```bash
composer update nowo-tech/password-strength-bundle
```

- **Runtime / API** — unchanged from 1.2.0; no form, validator, or config migrations.
- **Contributors** — after cloning, run `make setup-hooks` so commit messages cannot include Cursor co-author trailers (see [CONTRIBUTING.md](CONTRIBUTING.md) and [GITHUB_CI.md](GITHUB_CI.md)).

## Unreleased / 2.x

Breaking or notable changes in future 2.x releases will be documented here.
