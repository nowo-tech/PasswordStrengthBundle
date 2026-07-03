# Upgrading

This document describes how to upgrade between versions of Password Strength Bundle.

## 1.0.0 (2026-07-03)

First stable release. No upgrade steps when installing for the first time.

- **PHP:** >= 8.1, < 8.6
- **Symfony:** ^6.0 || ^7.0 || ^8.0
- **Composer:** `composer require nowo-tech/password-strength-bundle`
- **Assets:** `php bin/console assets:install` and include `bundles/passwordstrength/password-strength.js` in your layout (see [INSTALLATION.md](INSTALLATION.md)).
- **PasswordToggleBundle** is optional; when not installed the bundle uses Symfony `PasswordType` automatically.

## Unreleased / 1.x

Breaking or notable changes in future 1.x releases will be documented here.
