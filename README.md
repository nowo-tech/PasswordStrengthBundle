# Password Strength Bundle

[![CI](https://github.com/nowo-tech/PasswordStrengthBundle/actions/workflows/ci.yml/badge.svg)](https://github.com/nowo-tech/PasswordStrengthBundle/actions/workflows/ci.yml) [![Packagist Version](https://img.shields.io/packagist/v/nowo-tech/password-strength-bundle.svg?style=flat)](https://packagist.org/packages/nowo-tech/password-strength-bundle) [![Packagist Downloads](https://img.shields.io/packagist/dt/nowo-tech/password-strength-bundle.svg)](https://packagist.org/packages/nowo-tech/password-strength-bundle) [![License](https://img.shields.io/badge/license-MIT-blue.svg)](LICENSE) [![PHP](https://img.shields.io/badge/PHP-8.2%2B-777BB4?logo=php)](https://php.net) [![Symfony](https://img.shields.io/badge/Symfony-6.0%2B%20%7C%207.4%2B%20%7C%208.0%20%7C%208.1%2B-000000?logo=symfony)](https://symfony.com) [![GitHub stars](https://img.shields.io/github/stars/nowo-tech/password-strength-bundle.svg?style=social&label=Star)](https://github.com/nowo-tech/PasswordStrengthBundle) [![Coverage](https://img.shields.io/badge/Coverage-99%25%20PHP%20%7C%2090%25%20TS-brightgreen)](#tests-and-coverage)

> ⭐ **Found this useful?** Give it a **star** on [GitHub](https://github.com/nowo-tech/PasswordStrengthBundle) so more developers can find it.

Symfony bundle that extends `PasswordType` with **100% configurable** password hardness: predefined **levels**, inline **conditions**, auto-built **HTML pattern**, live **TypeScript** feedback, and a **validator** constraint.

![FrankenPHP Friendly Worker Mode](docs/images/frankenphp-friendly.png)

This bundle is **FrankenPHP worker mode friendly**.

## Table of contents

- [Features](#features)
- [Installation](#installation)
- [Requirements](#requirements)
- [Configuration](#configuration)
- [Usage](#usage)
- [PasswordToggleBundle compatibility (optional)](#passwordtogglebundle-compatibility-optional)
- [Demo](#demo)
- [Documentation](#documentation)
- [Tests and coverage](#tests-and-coverage)
- [License](#license)

## Features

- **PasswordStrengthType** — extends Symfony `PasswordType`
- **Policy modes** — `level` (weak/medium/strong/custom) or inline `conditions`
- **HTML `pattern`** — built automatically from the active policy
- **Live feedback** — TypeScript evaluates requirements and shows what is missing
- **Feedback position** — configurable `above` or `below` the input
- **PasswordStrength validator** — server-side enforcement with the same rules
- **Multi-framework Twig themes** — Bootstrap 3–5, Tailwind 2, Foundation 5–6, table/div layouts
- **PasswordToggleBundle** — optional show/hide toggle when that bundle is installed (auto-detected)
- **Vite + pnpm + TypeScript** assets (IIFE bundle for `assets:install`)
- **Translations** — DE, EN, ES, FR, IT, NL, PT (overridable from the app)

## Installation

```bash
composer require nowo-tech/password-strength-bundle
```

Register the bundle (Flex does this automatically):

```php
Nowo\PasswordStrengthBundle\PasswordStrengthBundle::class => ['all' => true],
```

Install public assets:

```bash
php bin/console assets:install
```

Include the script in your layout (or demo template):

```twig
<script src="{{ asset('password-strength.js', 'nowo_password_strength') }}" defer></script>
```

## Requirements

- PHP >= 8.1, < 8.6
- Symfony >= 6.0 || >= 7.0 || >= 8.0

## Configuration

```yaml
# config/packages/nowo_password_strength.yaml
nowo_password_strength:
  form_theme: bootstrap_5_layout.html.twig  # match your app layout
  feedback_position: below
  show_requirements: true
  live_feedback: true
  default_level: medium
  levels:
    weak:
      min_length: 6
    medium:
      min_length: 8
      require_lowercase: true
      require_uppercase: true
      require_digit: true
    strong:
      min_length: 12
      require_lowercase: true
      require_uppercase: true
      require_digit: true
      require_special: true
  use_password_toggle: true   # optional; auto-uses toggle when PasswordToggleBundle is installed
  # parent_form_type: ~      # null = auto; or set a FQCN to force a specific parent PasswordType
  generator_mode: off
```

## Usage

```php
use Nowo\PasswordStrengthBundle\Form\PasswordStrengthType;

$builder->add('plainPassword', PasswordStrengthType::class, [
    'policy_mode' => 'level',
    'level' => 'strong',
    'feedback_position' => 'below',
    'ui_framework' => 'bootstrap5',
]);
```

### Form type (inline conditions)

```php
$builder->add('plainPassword', PasswordStrengthType::class, [
    'policy_mode' => 'conditions',
    'conditions' => [ /* ... */ ],
    'generator_mode' => 'modal',  // off | input | modal
    'generator_count' => 4,       // suggestions in modal
]);
```

**Generator modes:**
- `off` — no generator (default)
- `input` — one click fills the field as visible text
- `modal` — opens a dialog with suggestions, copy and “Use this password”

### Validator constraint

```php
use Nowo\PasswordStrengthBundle\Validator\PasswordStrength;

#[PasswordStrength(policyMode: 'level', level: 'medium')]
public ?string $plainPassword = null;
```

### Available condition keys

| Key | Type | Description |
|-----|------|-------------|
| `min_length` | int | Minimum length |
| `max_length` | int | Maximum length |
| `require_lowercase` | bool | At least one `a-z` |
| `require_uppercase` | bool | At least one `A-Z` |
| `require_digit` | bool | At least one digit |
| `require_special` | bool | At least one special char |
| `special_chars` | string | Charset for special chars |
| `disallow_whitespace` | bool | No spaces |
| `not_contain` | string[] | Forbidden substrings |
| `min_unique_chars` | int | Minimum unique characters |
| `regex` | string | Custom regex (server + pattern) |

## PasswordToggleBundle compatibility (optional)

`PasswordToggleBundle` is **not required**. With default settings the bundle works standalone using Symfony `PasswordType`.

When `PasswordToggleBundle` is installed, `PasswordStrengthType` automatically extends its `PasswordType` and renders the eye toggle **alongside** strength feedback and the optional password generator.

```bash
composer require nowo-tech/password-toggle-bundle symfony/ux-icons symfony/http-client
```

```yaml
nowo_password_strength:
  use_password_toggle: true   # default; ignored if parent_form_type is set explicitly
  # Force Symfony parent even when PasswordToggleBundle is installed:
  # parent_form_type: Symfony\Component\Form\Extension\Core\Type\PasswordType
  # Force PasswordToggle parent (requires the bundle):
  # parent_form_type: Nowo\PasswordToggleBundle\Form\Type\PasswordType

nowo_password_toggle:
  toggle: true
  visible_icon: 'tabler:eye-off'
  hidden_icon: 'tabler:eye'
```

Per field:

```php
$builder->add('password', PasswordStrengthType::class, [
    'use_password_toggle' => true,  // false = plain password input
    'generator_mode' => 'input',
]);
```

The generator syncs toggle icon/aria state when it fills the field as visible text.

## Demo

```bash
make -C demo up
# or: make -C demo/symfony8 up
```

Open **http://localhost:8021/en/** (redirect from `/`; set `PORT` in `demo/symfony8/.env` if needed).

## Documentation

- [GitHub Actions CI requirements](docs/GITHUB_CI.md)
- [Installation](docs/INSTALLATION.md)
- [Configuration](docs/CONFIGURATION.md)
- [PSR evaluation (REQ-CS-007)](docs/PSR.md)
- [Usage](docs/USAGE.md)
- [Contributing](docs/CONTRIBUTING.md)
- [Code of Conduct](CODE_OF_CONDUCT.md)
- [Changelog](docs/CHANGELOG.md)
- [Upgrading](docs/UPGRADING.md)
- [Release](docs/RELEASE.md)
- [Security](docs/SECURITY.md)
- [Engram](docs/ENGRAM.md)
- [Spec-driven development](docs/SPEC-DRIVEN-DEVELOPMENT.md)
- [GitHub Spec Kit](docs/SPEC-KIT.md)

### Additional documentation

- [Demo with FrankenPHP](docs/DEMO-FRANKENPHP.md)
- [Demo (Symfony 8)](demo/symfony8/README.md)

## Tests and coverage

| Language | Coverage (approx.) |
|----------|-------------------|
| PHP | ~99% lines (`make test-coverage`) |
| TypeScript | ~90% (`make test-ts`) |

```bash
make test
make test-ts
make test-coverage
```

## License

MIT — see [LICENSE](LICENSE).
