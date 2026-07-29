# Installation


## Table of contents

- [Requirements](#requirements)
- [Install with Composer](#install-with-composer)
- [Register the bundle](#register-the-bundle)
  - [With Symfony Flex](#with-symfony-flex)
  - [Manual registration](#manual-registration)
  - [AssetMapper](#assetmapper)
- [Optional: PasswordToggleBundle](#optional-passwordtogglebundle)
- [Twig and translation overrides](#twig-and-translation-overrides)
- [Next steps](#next-steps)

## Requirements

- **PHP** >= 8.1, < 8.6
- **Symfony** ^6.0 || ^7.0 || ^8.0
- **symfony/form**, **symfony/framework-bundle**, **symfony/twig-bundle**, **symfony/validator**, **symfony/translation**

Optional:

- **[nowo-tech/password-toggle-bundle](https://github.com/nowo-tech/PasswordToggleBundle)** — show/hide toggle (auto-detected when installed).
- **symfony/ux-icons** + **symfony/http-client** — only if you use PasswordToggleBundle with the default tabler icons.

## Install with Composer

```bash
composer require nowo-tech/password-strength-bundle
```

## Register the bundle

### With Symfony Flex

If a Flex recipe is configured (`.symfony/recipes/nowo-tech/password-strength-bundle/`), the bundle is registered and `config/packages/nowo_password_strength.yaml` is created automatically.

### Manual registration

1. Register in `config/bundles.php`:

```php
return [
    // ...
    Nowo\PasswordStrengthBundle\PasswordStrengthBundle::class => ['all' => true],
];
```

2. Create `config/packages/nowo_password_strength.yaml` (see [CONFIGURATION.md](CONFIGURATION.md)).

3. Publish assets:

```bash
php bin/console assets:install
```

4. Include the script in your layout:

```twig
<script src="{{ asset('password-strength.js', 'nowo_password_strength') }}" defer></script>
```

The bundle prepends its form theme to `twig.form_themes` according to `form_theme` in configuration (default: `form_div_layout.html.twig`). Set `form_theme` to match your app layout (e.g. `bootstrap_5_layout.html.twig`).

### AssetMapper

If your app uses [Symfony AssetMapper](https://symfony.com/doc/current/frontend/asset_mapper.html), the bundle registers the `nowo_password_strength` asset package. Run `assets:install` once so `password-strength.js` is published to `public/bundles/passwordstrength/`.

## Optional: PasswordToggleBundle

```bash
composer require nowo-tech/password-toggle-bundle symfony/ux-icons symfony/http-client
```

Register `NowoPasswordToggleBundle`, add UX Icons config, and lock tabler icons — see [PasswordToggleBundle INSTALLATION](https://github.com/nowo-tech/PasswordToggleBundle/blob/main/docs/INSTALLATION.md).

With default settings (`use_password_toggle: true`), this bundle detects PasswordToggleBundle and extends its `PasswordType` automatically.

## Twig and translation overrides

- Templates: `templates/bundles/NowoPasswordStrengthBundle/...`
- Translations: `translations/NowoPasswordStrengthBundle.{locale}.yaml`

Bundled locales: `de`, `en`, `es`, `fr`, `it`, `nl`, `pt`.

See [USAGE.md](USAGE.md#overriding-bundle-templates-and-translations).

## Next steps

- [Configuration](CONFIGURATION.md)
- [Usage](USAGE.md)
- [Demo with FrankenPHP](DEMO-FRANKENPHP.md)
