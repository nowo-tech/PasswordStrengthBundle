# Upgrading

## Table of contents

- [Unreleased](#unreleased)
- [To 2.9.0](#to-290)
- [To 2.8.1](#to-281)
- [To 2.8.0](#to-280)
- [To 2.7.0](#to-270)
- [To 2.6.0](#to-260)
- [To 2.6.0](#to-260-1)
- [To 2.5.4](#to-254)
- [To 2.5.3](#to-253)
- [To 2.5.2](#to-252)
- [To 2.5.1](#to-251)
- [To 2.5.0](#to-250)
- [To 2.4.0](#to-240)
- [To 2.3.4](#to-234)
- [To 2.3.3](#to-233)
- [To 2.3.2](#to-232)
- [From 2.3.0 to 2.3.1](#from-230-to-231)
- [From 2.2.3 to 2.3.0](#from-223-to-230)
- [From 2.2.2 to 2.2.3](#from-222-to-223)
- [To 2.2.2](#to-222)
- [To 2.2.1](#to-221)
- [To 2.2.0](#to-220)
- [2.1.0 (2026-07-29)](#210-2026-07-29)
- [2.0.0 (2026-07-22)](#200-2026-07-22)
- [1.0.0 (2026-07-03)](#100-2026-07-03)
- [1.1.0 (2026-07-09)](#110-2026-07-09)
- [1.2.0 (2026-07-13)](#120-2026-07-13)
- [1.3.0 (2026-07-16)](#130-2026-07-16)
- [Unreleased / 2.x](#unreleased--2x)
  - [Twig Extra Bundle (REQ-TWIG-004)](#twig-extra-bundle-req-twig-004)
  - [Twig-CS-Fixer (maintainers)](#twig-cs-fixer-maintainers)

## Unreleased

## To 2.9.0

From **2.8.1**:

```bash
composer update nowo-tech/password-strength-bundle
```

Named condition sets now use the canonical `default_profile` + `profiles` keys (REQ-CFG-001). No change is required, but:

1. **Rename (recommended).** In `config/packages/nowo_password_strength.yaml` rename `default_level` → `default_profile` and `levels` → `profiles`. The old names are still accepted and mapped onto the new ones; using both names in the same file fails at `cache:clear`.

   ```yaml
   nowo_password_strength:
     default_profile: medium   # was default_level
     profiles:                 # was levels
       admin:
         min_length: 16
   ```

2. **Built-in profiles stay available.** `weak`, `medium` and `strong` are now always defined; your profiles are added next to them and one with the same name replaces the built-in one. If you configured `levels` with only custom names, the three built-ins reappear (fields asking for `level: medium` now get the built-in medium policy instead of an empty one).
3. **`default_profile` is validated.** It must name an existing profile (built-in or configured). A configuration whose `default_level` pointed at a missing name — which used to resolve to no conditions at all — now fails at `cache:clear` with `default_profile "…" must exist under nowo_password_strength.profiles`. Fix the name or add the profile.
4. **Parameters.** Prefer `%nowo_password_strength.profiles%` / `%nowo_password_strength.default_profile%`; `%nowo_password_strength.levels%` / `%nowo_password_strength.default_level%` remain as aliases.

The form and constraint option keeps its name: `level` selects a profile (`'level' => 'admin'`, `#[PasswordStrength(level: 'admin')]`).

## To 2.8.1

From **2.8.0**:

```bash
composer update nowo-tech/password-strength-bundle
```

## To 2.8.0

From **2.7.0**: `composer update nowo-tech/password-strength-bundle`. Optional: if you copied `password_strength_widget`
only to translate the widget copy in `NowoPasswordStrengthBundle` while your labels use another domain, set
`nowo_password_strength.ui_translation_domain: NowoPasswordStrengthBundle` and keep only the blocks you really change
(e.g. `password_strength_generate_button_class`).

## To 2.7.0

From **2.6.0**:

```bash
composer update nowo-tech/password-strength-bundle
```

- `nowo_password_strength.levels` is validated: only the documented condition keys (`min_length`, `max_length`, `require_lowercase`, `require_uppercase`, `require_digit`, `require_special`, `special_chars`, `disallow_whitespace`, `not_contain`, `min_unique_chars`, `regex`, `regex_message`, and the short aliases `lowercase`, `uppercase`, `digit`, `special`, `no_whitespace`) are accepted. Fix any misspelled key reported at `cache:clear`.

## To 2.6.0

From **2.5.4**:

```bash
composer update nowo-tech/password-strength-bundle
```

## To 2.6.0

From **2.5.x**:

```bash
composer update nowo-tech/password-strength-bundle
```

No breaking change: the default `csp_nonce_attribute: csp_nonce` keeps the 2.5 behaviour (the nonce of the `csp_nonce`
request attribute on the injected base `<style>`). Hosts whose nonce lives in another request attribute set
`csp_nonce_attribute: <name>`; `csp_nonce_attribute: ''` turns the nonce off.

**Retire the CSP sentinel.** If your app pre-declares `<style id="password-strength-inline-styles" nonce="…"></style>`
(e.g. `templates/_password_strength_csp.html.twig`) so the script does not inject a `<style>` that a nonce-based
`style-src-elem` would block, delete that template and every `{% include '_password_strength_csp.html.twig' %}`: the
bundle now stamps the request nonce on the element it injects, also when your form theme overrides the
`password_strength_widget` block (the nonce travels on the input as `data-password-strength-nonce`). No configuration
is needed with the default `csp_nonce` attribute. Note that the injected base rules then apply (input row, generator
modal); keep your own CSS for anything you style differently, or link `bundles/passwordstrength/password-strength.css`
to skip the injection entirely.

## To 2.5.4

From **2.5.3**:

```bash
composer update nowo-tech/password-strength-bundle
```

## To 2.5.3

From **2.5.2**:

```bash
composer update nowo-tech/password-strength-bundle
```

No breaking changes and nothing to do if you registered a bundle theme (`@NowoPasswordStrengthBundle/Form/password_strength_theme*.html.twig`) in `twig.form_themes`: it keeps working, in any position, and no longer overrides your `form_row` / `form_label` / `form_errors` blocks. If you had moved it before your own theme only to work around that, you can leave it as is.

- The themes no longer `extends` a Symfony layout, so they rely on the base layout of your `twig.form_themes` (Symfony always registers `form_div_layout.html.twig` first) for shared blocks like `widget_attributes`.
- You can now `{% use '@NowoPasswordStrengthBundle/Form/password_strength_theme.html.twig' %}` (or any framework variant) inside your own form theme.
- If you overrode a theme under `templates/bundles/NowoPasswordStrengthBundle/Form/` and it starts with `{% extends 'form_div_layout.html.twig' %}`, remove that line (or switch it to `{% use %}` of the shipped theme); otherwise it still shadows your blocks.

## To 2.5.2

From **2.5.1**:

```bash
composer update nowo-tech/password-strength-bundle
```

No breaking changes. For a nonce-based Content Security Policy (`style-src` / `style-src-elem` with a nonce), either:

- **link the stylesheet** (recommended, nothing is injected):

  ```twig
  <link rel="stylesheet" href="{{ asset('password-strength.css', 'nowo_password_strength') }}">
  ```

- or keep the injected `<style>`: set the `csp_nonce` request attribute (the UiKit/AuthKit convention) and the form theme prints it as `data-nonce` on `<nowo-password-strength>`; alternatively expose it as `<meta name="csp-nonce" content="…">` or load `password-strength.js` with a `nonce` attribute.

A pre-declared `<style id="password-strength-inline-styles" nonce="…">` keeps working. If you copied `password_strength_theme.html.twig`, add the `data-nonce` attribute from the shipped theme to your `<nowo-password-strength>` root.

## To 2.5.1

From **2.5.0** — generated password keeps the toggle state in sync

```bash
composer update nowo-tech/password-strength-bundle
```

- No breaking changes. No application upgrade steps.

## To 2.5.0

From **2.4.0** — Symfony >=7.4 (lowest tested), vitest 4.

```bash
composer update nowo-tech/password-strength-bundle
```

- Symfony **6.x and 7.0–7.3** are no longer supported: the bundle requires `^7.4 || ^8.0`. Upgrade the host application to Symfony 7.4 LTS or 8.x first.

## To 2.4.0

From **2.3.4** — fixes - min_unique_chars and delimited regex patterns, attribute named arguments, translated validator message, default_level.

```bash
composer update nowo-tech/password-strength-bundle
```

- Policies using `min_unique_chars` no longer reject valid passwords (the generated pattern required a literal digit).
- A `regex` with flags other than `u` (e.g. `/…/i`) no longer produces an HTML `pattern` attribute; the server and JS checks still apply.
- The validator message now comes from the `validators` translation domain; `#[PasswordStrength(policyMode: …, level: …)]` named arguments now work.

## To 2.3.4

From **2.3.3** — dependency refresh and repository maintenance.

```bash
composer update nowo-tech/password-strength-bundle
```

- No breaking changes. No application upgrade steps.

## To 2.3.3

From **2.3.2** — dependency updates and demo Playwright e2e (REQ-DEMO-013).

```bash
composer update nowo-tech/password-strength-bundle
```

No breaking changes. No application upgrade steps.

## To 2.3.2

From **2.3.1** — REQ-CS-008 Igor FrankenPHP worker audit (igor-php require-dev, igor.json, make igor).

```bash
composer update nowo-tech/password-strength-bundle
php bin/console cache:clear
```

- No application upgrade steps for require-dev Igor tooling (REQ-CS-008). Consumers do not pull `igor-php/igor-php` transitively.

This document describes how to upgrade between versions of Password Strength Bundle.

## From 2.3.0 to 2.3.1

No application upgrade steps. Documents FrankenPHP worker compatibility when the kernel is **not** reset between requests (`FRANKENPHP_RESET_KERNEL` unset/false). See [FRANKENPHP-WORKER-AUDIT.md](FRANKENPHP-WORKER-AUDIT.md).

```bash
composer update nowo-tech/password-strength-bundle
```

## From 2.2.3 to 2.3.0

The default form theme now wraps the widget in `<nowo-password-strength>`. Load the same `password-strength.js` asset. If you copied the form theme, wrap the field in `<nowo-password-strength>` (legacy `data-password-strength-field` hosts still initialize). Nested PasswordToggle may render `<nowo-password-toggle>` when that bundle is installed.

```bash
composer update nowo-tech/password-strength-bundle
php bin/console assets:install
```

## From 2.2.2 to 2.2.3

Review the [CHANGELOG](CHANGELOG.md) entry. PHP **8.2+** may now be required.

```bash
composer update nowo-tech/password-strength-bundle
```

## To 2.2.2

No application upgrade steps.

```bash
composer update nowo-tech/password-strength-bundle
```

## To 2.2.1

No application upgrade steps. **Demos only:** Hot Reload Bundle `^1.4` (FrankenPHP Mercure/`hot_reload`, `dev`/`test`).

```bash
composer update nowo-tech/password-strength-bundle
php bin/console cache:clear
```

## To 2.2.0

From **2.1.0** — Adds required Twig Extra (REQ-TWIG-004) and Twig-CS-Fixer. Register `TwigExtraBundle` if Flex did not.

```bash
composer update nowo-tech/password-strength-bundle
php bin/console cache:clear
```

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

### Twig Extra Bundle (REQ-TWIG-004)

Hosts that render this bundle's Twig templates must install:

```bash
composer require twig/extra-bundle twig/string-extra
```

and enable `Twig\Extra\TwigExtraBundle\TwigExtraBundle`. Flex recipes usually register it automatically.

### Twig-CS-Fixer (maintainers)

Package maintainers: `composer twig:lint` / `composer twig:fix` use `.twig-cs-fixer.php` over `src/` (and `templates/` when present).

