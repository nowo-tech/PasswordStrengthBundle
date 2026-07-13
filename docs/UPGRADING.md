# Upgrading

This document describes how to upgrade between versions of Password Strength Bundle.

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
- **Overrides** — continue using `translations/PasswordStrengthBundle.{locale}.yaml` in your app to customize strings (see [INSTALLATION.md](INSTALLATION.md)).
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

## Unreleased / 1.x

Breaking or notable changes in future 1.x releases will be documented here.
