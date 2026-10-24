# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## Table of contents

- [[Unreleased]](#unreleased)
- [[2.8.0] - 2026-10-23](#280---2026-10-23)
- [[2.7.0] - 2026-10-22](#270---2026-10-22)
- [[2.6.0] - 2026-10-21](#260---2026-10-21)
- [[2.5.4] - 2026-10-20](#254---2026-10-20)
- [[2.5.3] - 2026-10-19](#253---2026-10-19)
- [[2.5.2] - 2026-10-19](#252---2026-10-19)
- [[2.5.1] - 2026-10-13](#251---2026-10-13)
- [[2.5.0] - 2026-10-13](#250---2026-10-13)
- [[2.4.0] - 2026-10-13](#240---2026-10-13)
- [[2.3.4] - 2026-10-13](#234---2026-10-13)
- [[2.3.3] - 2026-10-09](#233---2026-10-09)
- [[2.3.2] - 2026-09-27](#232---2026-09-27)
- [[2.3.1] - 2026-09-24](#231---2026-09-24)
- [[2.3.0] - 2026-09-03](#230---2026-09-03)
- [[2.2.3] - 2026-08-24](#223---2026-08-24)
- [[2.2.2] - 2026-08-19](#222---2026-08-19)
- [[2.2.1] - 2026-08-18](#221---2026-08-18)
- [[2.2.0] - 2026-08-04](#220---2026-08-04)
- [[2.1.0] - 2026-07-29](#210---2026-07-29)
- [[2.0.0] - 2026-07-22](#200---2026-07-22)
- [[1.3.0] - 2026-07-16](#130---2026-07-16)
- [[1.2.0] - 2026-07-13](#120---2026-07-13)
- [[1.1.0] - 2026-07-09](#110---2026-07-09)
- [[1.0.0] - 2026-07-03](#100---2026-07-03)

## [Unreleased]

### Changed

- Demo: `password_demo/show.html.twig` renders the form through the child loop instead of hard-coded `form_label` / `form_widget` / `form_errors` for `password` and `form_widget(form.submit)`; the row wrappers and the submit classes move to `row_attr` / `attr` in the demo form builder (REQ-TWIG-003).

## [2.8.0] - 2026-10-23

### Added

- `ui_translation_domain` (form option and `nowo_password_strength.ui_translation_domain`): the domain of the widget copy
  (requirements, generator), kept apart from the label's `translation_domain`. Hosts that translate labels in another
  domain no longer copy `password_strength_widget` to keep the bundle copy. `null` (default) keeps the 2.7 behaviour.

## [2.7.0] - 2026-10-22

Validated level presets, translation key parity for both domains, raw-form release gate, CI static analysis. See [UPGRADING](UPGRADING.md#to-270).

### Changed

- `nowo_password_strength.levels` entries are now validated key by key (known condition keys and short aliases only, non-negative lengths) instead of an unvalidated variable prototype; a typo such as `min_lenght` now fails at container compile time (REQ-SF-006). See [UPGRADING](UPGRADING.md).
- `make validate-translations` checks key parity of both domains (`NowoPasswordStrengthBundle`, `validators`) against `en`; `release-check` starts with `ensure-up` and runs `check-no-raw-html-form` (REQ-MAKE-002, REQ-MAKE-004); `make help` no longer mentions a non-existent `demo/symfony7` (REQ-MAKE-001).
- Demo `release-verify` / `release-check` sync the bundle, run the demo checks and require a final HTTP 200 (REQ-DEMO-007); CI runs PHPStan, Rector (dry run) and Igor (REQ-CS-006); the matrix documents that the Symfony 8.0 cells resolve to 8.1 (REQ-SF-002).
- USAGE documents the `validators` domain used by the constraint message (REQ-I18N-003); README publishes the measured coverage (REQ-TEST-007); the co-author check accepts git worktrees.

## [2.6.0] - 2026-10-21

The form type reads the CSP nonce from the request (csp_nonce_attribute) and stamps it on the input, so hosts drop their nonce include. See [UPGRADING](UPGRADING.md#to-260).

### Added

- **CSP nonce from the request (2.6):** new option `csp_nonce_attribute` (default `csp_nonce`, `''` disables). The form
  type reads the nonce from that request attribute itself (current request, then main request; read on every render,
  no state kept between requests — worker safe) and exposes it as the `csp_nonce` view variable (printed as
  `data-nonce` on `<nowo-password-strength>`) and as `data-password-strength-nonce` on the password input.
  `password-strength.js` also reads the input attribute, so host themes that copy the `password_strength_widget` block
  without `data-nonce` keep a nonce on the injected base `<style>`. Hosts can delete their
  `_password_strength_csp.html.twig` sentinel (see UPGRADING). Shipped `password-strength.js` rebuilt.

## [2.5.4] - 2026-10-20

Dependency refresh (bundle and demos), php-cs-fixer ParallelConfig(2). See [UPGRADING](UPGRADING.md#to-254).

### Changed

- Dependencies refreshed (Composer and pnpm/npm lock files of the bundle and its demos, campaign 2026-10-19); shipped assets rebuilt.
- `.php-cs-fixer.dist.php`: `new ParallelConfig(2)` (REQ-CS-001).
- Documentation: wave-1 REQ compliance — README documentation list order, CONTRIBUTING Cursor Agent settings and post-release-commit trailer check, canonical bug-report template, demo compose project name `password-strength-bundle-demo-symfony-8`, doc TOCs, Spec Kit inventory refresh (62/62, new FR-UI-006 for the shipped stylesheet).

## [2.5.3] - 2026-10-19

Form themes no longer shadow the host's form_row / form_label / form_errors blocks (no extends; usable in twig.form_themes or via {% use %}). See [UPGRADING](UPGRADING.md#to-253).

### Fixed

- **Form themes no longer shadow the host's blocks:** `password_strength_theme.html.twig` extended `form_div_layout.html.twig`, so registering it in `twig.form_themes` after the host's own theme replaced the host's `form_row` / `form_label` / `form_errors` blocks (labels lost their classes, errors rendered above the input). Every shipped theme now contains only the bundle's blocks (no `extends`; the framework variants `{% use %}` the base theme) and works both in `twig.form_themes` (any position) and through `{% use %}`. Shared blocks such as `widget_attributes` resolve from the host's base layout. The CSP `data-nonce` from 2.5.2 is unchanged. Regression test renders a host theme plus every bundle theme and asserts the host's row, label, errors markup and order win.

## [2.5.2] - 2026-10-19

CSP nonce on the injected base style; optional password-strength.css. See [UPGRADING](UPGRADING.md#to-252).

### Fixed

- **CSP:** `password-strength.js` injected its base `<style>` without a nonce, so hosts with a nonce-based `style-src` / `style-src-elem` got a CSP violation on every page with the widget. The injected element now carries a nonce read from `data-nonce` on the widget root (the form theme prints the `csp_nonce` request attribute there), then `<meta name="csp-nonce">`, then the nonce of the `<script>` that loaded the bundle. A host-declared `<style id="password-strength-inline-styles" nonce>` is still honoured (nothing is injected).

### Added

- `password-strength.css` (published with `assets:install`): link it from your layout and the script injects nothing. A linked `password-strength*.css` or the `--nowo-password-strength-css` marker of a bundled copy is detected.

## [2.5.1] - 2026-10-13

### Fixed

- **PasswordToggleBundle integration:** applying a generated password now reveals it through the toggle button's `is-password-visible` class (PasswordToggleBundle >= 2.1.1, CSP-safe) instead of inline `style.display` on the eye icons, which left the toggle out of sync (no icon / wrong icon, and the next click hid the password again without updating the icon). Shipped `password-strength.js` rebuilt; TS regression test added.
- **Docs / demo:** INSTALLATION now says to load `toggle_password.css` when PasswordToggleBundle is installed; the Symfony 8 demo did not, so both eye icons rendered side by side.

## [2.5.0] - 2026-10-13

### Changed

- Minimum Symfony version is now **7.4** (`^7.4 || ^8.0`). Symfony 6.x could not be installed with the dev dependencies (password-toggle-bundle requires `^7.0`) and the CI "7.0" cell resolved to the latest 7.x anyway; the cell is removed and the coverage job runs on 7.4.
- Dev tooling: `vitest` and `@vitest/coverage-v8` upgraded from `^2.1.9` to `^4.1.11` (no config change needed; shipped assets unchanged).

## [2.4.0] - 2026-10-13

### Fixed

- `min_unique_chars` no longer emits a broken HTML/server pattern (`{0,}3`) that required a literal digit and rejected valid passwords; unique characters are counted by the evaluators only.
- A custom `regex` written with delimiters (e.g. `/^[a-z]+$/i`) is evaluated as given instead of being wrapped again (which rejected every password). The HTML `pattern` attribute gets the delimiter-free body, and is omitted when flags other than `u` cannot be expressed in HTML.
- `#[PasswordStrength(policyMode: …, level: …, conditions: …)]` now works as documented (the constraint has a named-argument constructor, also accepting `message`, `groups` and `payload`).
- The validator violation now fills `%missing%` (and still `{{ missing }}`), and `password_strength.invalid` ships in the `validators` translation domain for all bundled locales, so users see the translated message instead of the raw key.
- `default_level` is now honoured by the form type (default `level` option) and by the policy resolver / `PasswordStrength` constraint when no level is given.
- An unknown `policyMode` on the constraint raises a `ConstraintDefinitionException` with the allowed values instead of a bare `ValueError`.

## [2.3.4] - 2026-10-13

### Changed

- Development: `composer.json` pins `config.platform.php` to 8.2.0 so the committed lock stays installable on the minimum PHP; CI overrides the platform per matrix cell.
- Repository: `.twig-cs-fixer.cache` is now git-ignored.

### Dependencies

- `nowo-tech/password-toggle-bundle` 2.2.3 → 2.3.0 (development lock; #56).
- `@types/node` 26.6.4 → 26.6.5 (frontend tooling; #57).
- `happy-dom` 20.14.5 → 20.14.6 (frontend tooling; #58).

## [2.3.3] - 2026-10-09

### Added

- **REQ-DEMO-013:** Playwright e2e under `demo/symfony8/e2e/` (`make test-e2e`), `demo-screenshots` target, and README gallery (`docs/images/demo/overview.png`, `interaction.png`).

### Fixed

- `pnpm-lock.yaml` re-synced with `package.json` (vite ^8.3.2) so frozen-lockfile installs work again.

### Dependencies

- Bundle lock: `symfony/validator` v7.4.20 and other Symfony 7.4 patch releases.
- Dev tooling: `phpstan/phpstan` 2.3.1, `phpstan/phpstan-phpunit` 2.1.1, `phpstan/phpstan-symfony` 2.1.0, `rector/rector` 2.7.0, `phpunit/phpunit` 11.5.57, `igor-php/igor-php` ^0.10 (v0.10.1), `nowo-tech/phpstan-frankenphp` v1.2.3, `nowo-tech/password-toggle-bundle` v2.2.3.
- JS dev: Vite 8.3.4, `@types/node` 26.6.4.
- Demo (Symfony 8): Symfony v8.1.8, `twig/twig` v3.30.0, `twig/extra-bundle` v3.29.0, `symfony/ux-icons` v3.5.1, `nowo-tech/password-toggle-bundle` v2.2.3, `nowo-tech/hot-reload-bundle` v1.5.5, `nowo-tech/twig-inspector-bundle` v1.1.7.

## [2.3.2] - 2026-09-27

### Added

- **REQ-CS-008:** `igor-php/igor-php` (require-dev only), root `igor.json`, Composer/`Makefile` `igor` target, and `release-check` wiring for FrankenPHP worker-state audit.

### Changed

- **Worker safety (Igor):** justified `// @igor-ignore` annotations and/or `ResetInterface` / request-scoped fixes so `make igor` passes on package `src/`.

[2.3.2]: https://github.com/nowo-tech/PasswordStrengthBundle/releases/tag/v2.3.2

## [2.3.1] - 2026-09-24

### Changed

- **`PasswordPatternBuilder`:** marked `final readonly` (already property-free; aligns with other shared services under worker reuse).
- **PHPStan:** use `ruleset-worker-strict.neon` (includes worker rules + request-superglobal hygiene) alongside classic.

### Documentation

- Added [FRANKENPHP-WORKER-AUDIT.md](FRANKENPHP-WORKER-AUDIT.md) (scenario A vs B; verdict viable under **B** — no kernel reset).
- USAGE, README, DEMO-FRANKENPHP, baseline spec `FR-WORKER-001`–`003`.

### Notes

- **No breaking API or configuration changes.** Safe with FrankenPHP worker and `FRANKENPHP_RESET_KERNEL` unset/false.

[2.3.1]: https://github.com/nowo-tech/PasswordStrengthBundle/releases/tag/v2.3.1

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

[Unreleased]: https://github.com/nowo-tech/PasswordStrengthBundle/compare/v2.7.0...HEAD
[2.7.0]: https://github.com/nowo-tech/PasswordStrengthBundle/compare/v2.6.0...v2.7.0
[2.6.0]: https://github.com/nowo-tech/PasswordStrengthBundle/compare/v2.5.4...v2.6.0
[2.5.4]: https://github.com/nowo-tech/PasswordStrengthBundle/compare/v2.5.3...v2.5.4
[2.5.3]: https://github.com/nowo-tech/PasswordStrengthBundle/compare/v2.5.2...v2.5.3
[2.5.2]: https://github.com/nowo-tech/PasswordStrengthBundle/compare/v2.5.1...v2.5.2
[2.5.1]: https://github.com/nowo-tech/PasswordStrengthBundle/compare/v2.5.0...v2.5.1
[2.5.0]: https://github.com/nowo-tech/PasswordStrengthBundle/compare/v2.4.0...v2.5.0
[2.4.0]: https://github.com/nowo-tech/PasswordStrengthBundle/compare/v2.3.4...v2.4.0
[2.3.4]: https://github.com/nowo-tech/PasswordStrengthBundle/compare/v2.3.3...v2.3.4
[2.3.3]: https://github.com/nowo-tech/PasswordStrengthBundle/compare/v2.3.2...v2.3.3
[2.3.1]: https://github.com/nowo-tech/PasswordStrengthBundle/compare/v2.3.0...v2.3.1
[2.3.0]: https://github.com/nowo-tech/PasswordStrengthBundle/compare/v2.2.3...v2.3.0
[2.1.0]: https://github.com/nowo-tech/PasswordStrengthBundle/compare/v2.0.0...v2.1.0
[2.0.0]: https://github.com/nowo-tech/PasswordStrengthBundle/compare/v1.3.0...v2.0.0
[1.3.0]: https://github.com/nowo-tech/PasswordStrengthBundle/compare/v1.2.0...v1.3.0
[1.2.0]: https://github.com/nowo-tech/PasswordStrengthBundle/compare/v1.1.0...v1.2.0
[1.1.0]: https://github.com/nowo-tech/PasswordStrengthBundle/compare/v1.0.0...v1.1.0
[1.0.0]: https://github.com/nowo-tech/PasswordStrengthBundle/releases/tag/v1.0.0
