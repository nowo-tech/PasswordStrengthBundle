# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).


## Table of contents

- [[Unreleased]](#unreleased)
- [[2.3.0] - 2026-09-03](#230---2026-09-03)
- [[2.2.1] - 2026-08-18](#221---2026-08-18)
- [[2.2.0] - 2026-08-04](#220---2026-08-04)
- [[2.1.0] - 2026-07-29](#210---2026-07-29)
  - [Added](#added)
  - [Changed](#changed)
- [[2.0.0] - 2026-07-22](#200---2026-07-22)
  - [Changed](#changed-1)
  - [Migration](#migration)
- [[1.3.0] - 2026-07-16](#130---2026-07-16)
  - [Added](#added-1)
  - [Changed](#changed-2)
- [[1.2.0] - 2026-07-13](#120---2026-07-13)
  - [Added](#added-2)
  - [Changed](#changed-3)
- [[1.1.0] - 2026-07-09](#110---2026-07-09)
  - [Added](#added-3)
  - [Changed](#changed-4)
- [[1.0.0] - 2026-07-03](#100---2026-07-03)
  - [Added](#added-4)
  - [Requirements](#requirements)

## [Unreleased]

## [2.3.0] - 2026-09-03

### Changed

- **Web Component:** the form theme now renders `<nowo-password-strength>` (light DOM). `password-strength.js` defines the custom element and still initializes legacy `[data-password-strength-field]` hosts. Nested PasswordToggle uses `<nowo-password-toggle>` when that bundle is present.
- **Deps (dev):** bump `rector/rector`, `friendsofphp/php-cs-fixer`, `@types/node`, `happy-dom`; refresh Composer lockfiles.

### Notes

- Integrators: keep loading `password-strength.js`. Custom theme copies should wrap the field in `<nowo-password-strength>`.

[2.3.0]: https://github.com/nowo-tech/PasswordStrengthBundle/releases/tag/v2.3.0

## [2.2.3] - 2026-08-24

### Changed

- Raise minimum PHP to **8.2** and sync README badge (REQ-SF-001).
- **Docs:** PHP-FIG PSR evaluation (REQ-CS-007).

### Notes

- **No API or configuration changes** for integrators unless noted above.

[2.2.3]: https://github.com/nowo-tech/PasswordStrengthBundle/releases/tag/v2.2.3

## [2.2.2] - 2026-08-19

### Security

- **CI:** run `composer audit --locked` after dependency install (REQ-SEC / P3).

## [2.2.1] - 2026-08-18

### Changed

- **Demos:** pin `nowo-tech/hot-reload-bundle` to `^1.4` with FrankenPHP Mercure/`hot_reload` (`dev`/`test` only).

[2.2.1]: https://github.com/nowo-tech/PasswordStrengthBundle/releases/tag/v2.2.1

## [2.2.0] - 2026-08-04

### Added
- **REQ-TWIG-004:** require `twig/extra-bundle` + `twig/string-extra`; `make check-twig-extra` in `release-check`; demos register `TwigExtraBundle`.
- **Twig-CS-Fixer:** `vincentlanglet/twig-cs-fixer`, `.twig-cs-fixer.php`, `composer twig:lint` / `twig:fix`.

[2.2.0]: https://github.com/nowo-tech/PasswordStrengthBundle/releases/tag/v2.2.0

## [2.1.0] - 2026-07-29

### Added

- **FrankenPHP friendly** — badge/image in README and stronger demo/docs coverage for worker mode (`docs/images/frankenphp-friendly.png`, demo entrypoint).
- **Release gates** — `make coverage-check` (fail under 99% PHP lines), `make check-open-prs` (REQ-REL-003), wired into `make release-check`.
- **PHPStan FrankenPHP** — `nowo-tech/phpstan-frankenphp` classic + worker rulesets in `phpstan.neon.dist`.
- **Deprecation hard-fail** — `SYMFONY_DEPRECATIONS_HELPER=max[direct]=0` in PHPUnit and CI (REQ-SF-005).
- **Documentation** — table of contents across integrator/maintainer docs; expanded INSTALLATION, USAGE, CONTRIBUTING, GITHUB_CI, DEMO-FRANKENPHP.

### Changed

- **Makefile** — prefers Docker Compose V2 (`docker compose`), optional monorepo `update-deps` include, `demo-smoke` target.
- **PHPStan** — empty `ignoreErrors`; report unmatched ignores.
- **Code cleanup** — `PasswordStrengthBundle::getContainerExtension()`, form type imports, PasswordToggle integration typing.

## [2.0.0] - 2026-07-22

### Changed

- **Twig namespace** — `@PasswordStrengthBundle` renamed to `@NowoPasswordStrengthBundle` (form themes and logical template names).
- **Translation domain** — files and default `translation_domain` renamed from `PasswordStrengthBundle` to `NowoPasswordStrengthBundle` (`de`/`en`/`es`/`fr`/`it`/`nl`/`pt`).
- **TwigPathsPass** — resolves Twig filesystem loader aliases more robustly; prepends `templates/bundles/NowoPasswordStrengthBundle` when that directory exists so app overrides win.
- **Documentation** — INSTALLATION, USAGE, UPGRADING, and Spec Kit inventory updated for the new namespace/domain.

### Migration

See [UPGRADING.md](UPGRADING.md#200-2026-07-22) for override path and translation file renames.

## [1.3.0] - 2026-07-16

### Added

- **Code of Conduct** — Contributor Covenant (`CODE_OF_CONDUCT.md`), linked from README and CONTRIBUTING.
- **Git hygiene (REQ-GIT-001)** — CI job and scripts to reject Cursor `Co-authored-by` trailers; `.githooks/commit-msg`, `make setup-hooks`, `make check-no-cursor-coauthor`, and [`GITHUB_CI.md`](GITHUB_CI.md).
- **Tests** — additional coverage for `PasswordStrengthType` and `PasswordStrengthEvaluator`.

### Changed

- **Release checklist** — `make release-check` runs `check-no-cursor-coauthor` first; RELEASE.md notes re-check before push.

## [1.2.0] - 2026-07-13

### Added

- **Asset package** — registers the `nowo_password_strength` Symfony assets package (`base_path: /bundles/passwordstrength`) for AssetMapper and consistent `asset()` usage.
- **AssetMapper docs** — installation notes in [`INSTALLATION.md`](INSTALLATION.md) for apps using Symfony AssetMapper.

### Changed

- **Documentation** — README, INSTALLATION, UPGRADING, Flex recipe `post-install.txt`, and demo templates now use `asset('password-strength.js', 'nowo_password_strength')` instead of a hard-coded public path.

## [1.1.0] - 2026-07-09

### Added

- **Translations** — German (`de`), French (`fr`), Italian (`it`), Dutch (`nl`), and Portuguese (`pt`) in addition to English and Spanish.
- **GitHub Spec Kit** — baseline spec and code inventory (`specs/001-baseline/`), operator manual [`SPEC-KIT.md`](SPEC-KIT.md), `.specify/` scaffolding, and Cursor Agent `speckit-*` skills.

### Changed

- [`SPEC-DRIVEN-DEVELOPMENT.md`](SPEC-DRIVEN-DEVELOPMENT.md) — documents the three-layer model (Spec Kit, product behavior, `REQ-*` traceability).
- Demo Symfony 8 Docker image and Composer lockfiles refreshed.

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

[Unreleased]: https://github.com/nowo-tech/PasswordStrengthBundle/compare/v2.2.1...HEAD
[2.1.0]: https://github.com/nowo-tech/PasswordStrengthBundle/compare/v2.0.0...v2.1.0
[2.0.0]: https://github.com/nowo-tech/PasswordStrengthBundle/compare/v1.3.0...v2.0.0
[1.3.0]: https://github.com/nowo-tech/PasswordStrengthBundle/compare/v1.2.0...v1.3.0
[1.2.0]: https://github.com/nowo-tech/PasswordStrengthBundle/compare/v1.1.0...v1.2.0
[1.1.0]: https://github.com/nowo-tech/PasswordStrengthBundle/compare/v1.0.0...v1.1.0
[1.0.0]: https://github.com/nowo-tech/PasswordStrengthBundle/releases/tag/v1.0.0
