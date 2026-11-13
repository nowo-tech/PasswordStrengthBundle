# Usage


## Table of contents

- [FrankenPHP worker mode](#frankenphp-worker-mode)
- [Basic form field](#basic-form-field)
- [Policy modes](#policy-modes)
  - [Level (preset)](#level-preset)
  - [Inline conditions](#inline-conditions)
- [Server-side enforcement (`enforce`)](#server-side-enforcement-enforce)
- [Validator constraint](#validator-constraint)
- [Generator](#generator)
- [Feedback position](#feedback-position)
- [HTML pattern](#html-pattern)
- [Styling: tokens and classes](#styling-tokens-and-classes)
- [Overriding bundle templates (REQ-TWIG-001)](#overriding-bundle-templates-req-twig-001)
- [Translation overrides (REQ-I18N-001)](#translation-overrides-req-i18n-001)
- [Screenshots](#screenshots)
- [Demo routes](#demo-routes)
- [Using it from a DDD / hexagonal application](#using-it-from-a-ddd--hexagonal-application)
- [Related bundles](#related-bundles)

## Screenshots

Cropped to the form widget (not the demo chrome):

| Requirements met | Unmet feedback |
|------------------|----------------|
| ![Requirements met](images/demo/overview.webp) | ![Unmet feedback](images/demo/interaction.webp) |

Regenerate with `make -C demo/symfony8 demo-screenshots` (REQ-DEMO-013).

## FrankenPHP worker mode

This bundle is compatible with FrankenPHP **worker** when the Symfony kernel is **reused** between requests (`FRANKENPHP_RESET_KERNEL` unset or `0` — the Runtime default). Shared services (`PolicyResolver`, `PasswordStrengthEvaluator`, `PasswordPatternBuilder`, `PasswordStrengthType`, `PasswordStrengthValidator`) hold only compiled configuration or are framework-managed per validation call. They do not need `kernel.reset`.

Full audit: [FRANKENPHP-WORKER-AUDIT.md](FRANKENPHP-WORKER-AUDIT.md). Demo runtime: [DEMO-FRANKENPHP.md](DEMO-FRANKENPHP.md).

## Basic form field

```php
use Nowo\PasswordStrengthBundle\Form\PasswordStrengthType;

$builder->add('password', PasswordStrengthType::class, [
    'policy_mode' => 'level',
    'level' => 'medium',
]);
```

The field value is a password string. The widget is the `<nowo-password-strength>` custom element (light DOM: native input + requirements list). Include `password-strength.js` after `assets:install`.

## Policy modes

### Level (preset)

```php
$builder->add('password', PasswordStrengthType::class, [
    'policy_mode' => 'level',
    'level' => 'strong', // a profile name: weak | medium | strong (built in) or one of your `profiles`
]);
```

`level` names a profile from `nowo_password_strength.profiles`; omitted or `null` uses `default_profile`, an unknown name throws an `InvalidOptionsException`. See [Profiles](CONFIGURATION.md#profiles).

### Inline conditions

```php
$builder->add('password', PasswordStrengthType::class, [
    'policy_mode' => 'conditions',
    'conditions' => [
        'min_length' => 10,
        'require_lowercase' => true,
        'require_uppercase' => true,
        'require_digit' => true,
        'require_special' => true,
        'not_contain' => ['password', '123456'],
    ],
]);
```

Inline `conditions` must use known keys (typos such as `min_lenght` throw) and hold at least one rule.

## Server-side enforcement (`enforce`)

By default (`enforce: true`) the field carries the `PasswordStrength` constraint built from its own `policy_mode` / `level` / `conditions`, so a submit that bypasses the browser checks is rejected server-side. Your own `constraints` are kept.

```php
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\NotCompromisedPassword;

$builder->add('plainPassword', PasswordStrengthType::class, [
    'level' => 'strong',
    'mapped' => false,
    'constraints' => [new NotBlank(), new NotCompromisedPassword()], // PasswordStrength is added for you
]);
```

- **Entity constraint as well?** If the mapped property already has `#[PasswordStrength]`, set `'enforce' => false` (or drop the attribute): with both, every failure is reported twice.
- **Custom `validation_groups`:** the attached constraint is in the `Default` group. If the form validates other groups only, set `'enforce' => false` and add `new PasswordStrength(level: 'strong', groups: ['registration'])` to `constraints`.
- `PasswordStrength` accepts an empty value: add `NotBlank` when the password is required, and `NotCompromisedPassword` for account passwords.
- Values over **4096 bytes** are always rejected (`max_length`), before any rule runs.
- `trim` defaults to `false` (like Symfony's `PasswordType`): surrounding spaces are part of the password.
- The conditions are serialized into the widget for live feedback: do not put secrets in `not_contain` or `regex`.

## Validator constraint

Use on an entity or DTO (for example when the password does not go through `PasswordStrengthType`, or for API input). With the form type, `enforce` already attaches it — use one or the other:

```php
use Nowo\PasswordStrengthBundle\Validator\PasswordStrength;

#[PasswordStrength(
    policyMode: 'level',
    level: 'medium',
)]
private ?string $password = null;
```

Or with inline conditions:

```php
#[PasswordStrength(
    policyMode: 'conditions',
    conditions: ['min_length' => 8, 'require_digit' => true],
)]
private ?string $password = null;
```

## Generator

```php
$builder->add('password', PasswordStrengthType::class, [
    'generator_mode' => 'modal', // off | input | modal
    'generator_count' => 5,
]);
```

## Feedback position

```php
$builder->add('password', PasswordStrengthType::class, [
    'feedback_position' => 'above',
]);
```

## HTML pattern

The widget sets `pattern` on the input from the active policy. Browsers may use it for native validation; **always rely on the `PasswordStrength` validator** for authoritative checks.

## Styling: tokens and classes

2.11. The base stylesheet (`password-strength.css`, linked or injected) reads host tokens instead of fixed values, so a
host restyles the generator modal and the input row without out-ranking it with `!important`. The tokens are never
declared by the bundle (set them on `:root` or a parent); each falls back to the earlier value.

| Token | Fallback | Where |
| --- | --- | --- |
| `--nowo-password-strength-row-gap` | `.5rem` | Input row (field + Generate) |
| `--nowo-password-strength-modal-z-index` | `1050` | Generator overlay |
| `--nowo-password-strength-overlay` / `-overlay-padding` | `rgba(0,0,0,.45)` / `1rem` | Generator overlay |
| `--nowo-password-strength-modal-bg` / `-modal-radius` / `-modal-max-width` / `-modal-shadow` / `-modal-padding` | `#fff` / `.5rem` / `32rem` / `0 .5rem 1rem rgba(0,0,0,.15)` / `1.25rem` | Modal box |
| `--nowo-password-strength-modal-title-size` / `-modal-title-margin` | `1.125rem` / `0 0 .5rem` | Modal title |
| `--nowo-password-strength-modal-hint-margin` / `-modal-hint-color` / `-modal-hint-size` | `0 0 .5rem` / `#6c757d` / `.9rem` | Hint under the title |
| `--nowo-password-strength-modal-list-margin` | `1rem 0` | Suggestions list |
| `--nowo-password-strength-modal-item-gap` / `-modal-item-align` / `-modal-item-spacing` | `.5rem` / `center` / `.5rem` | One suggestion (code + buttons) |
| `--nowo-password-strength-code-basis` / `-code-padding` / `-code-bg` / `-code-radius` / `-code-size` | `12rem` / `.35rem .5rem` / `#f8f9fa` / `.25rem` / `.95rem` | Suggested password |
| `--nowo-password-strength-modal-actions-gap` / `-modal-actions-margin-top` | `.5rem` / `1rem` | Close row |

Properties the base stylesheet does not set (the modal `color` and `border`, an item's border and background, the
actions' border) are plain host rules. Buttons and requirement colours come from the `ui_framework` preset classes
(Tailwind's `bg-blue-600`, Bootstrap's `btn-primary`); replace them with [`ui_classes`](CONFIGURATION.md) instead of
fighting them in CSS.

With UiKit (dark mode follows its tokens):

```css
:root {
  --nowo-password-strength-overlay: var(--nowo-ui-overlay, rgba(0, 0, 0, 0.45));
  --nowo-password-strength-modal-bg: var(--nowo-ui-surface, #fff);
  --nowo-password-strength-modal-radius: var(--nowo-ui-modal-radius, 0.5rem);
  --nowo-password-strength-modal-shadow: var(--nowo-ui-shadow-dialog, 0 0.5rem 1rem rgba(0, 0, 0, 0.15));
  --nowo-password-strength-code-bg: var(--nowo-ui-surface-muted, #f8f9fa);
  --nowo-password-strength-code-radius: var(--nowo-ui-radius, 0.25rem);
}
```

```yaml
nowo_password_strength:
    ui_classes:
        generate_button: nowo-ui-btn nowo-ui-btn--outline
        button_primary: nowo-ui-btn nowo-ui-btn--primary nowo-ui-btn--sm
        button_secondary: nowo-ui-btn nowo-ui-btn--outline nowo-ui-btn--sm
        requirement_met: password-strength-requirement password-strength-requirement--met
        requirement_unmet: password-strength-requirement password-strength-requirement--unmet
```

## Overriding bundle templates (REQ-TWIG-001)

Application overrides **always win** when placed under:

```
templates/bundles/NowoPasswordStrengthBundle/<subpath>
```

**Procedure**

1. Pick the `<subpath>` from the table below (same path as under `src/Resources/views/` in the package).
2. Create `templates/bundles/NowoPasswordStrengthBundle/<subpath>` in your app.
3. Clear cache in dev if needed: `php bin/console cache:clear`.

| Subpath | Purpose |
|---------|---------|
| `Form/password_strength_theme.html.twig` | Base div layout theme |
| `Form/password_strength_theme_table.html.twig` | Table layout theme |
| `Form/password_strength_theme_bootstrap3.html.twig` | Bootstrap 3 |
| `Form/password_strength_theme_bootstrap3_horizontal.html.twig` | Bootstrap 3 horizontal |
| `Form/password_strength_theme_bootstrap4.html.twig` | Bootstrap 4 |
| `Form/password_strength_theme_bootstrap4_horizontal.html.twig` | Bootstrap 4 horizontal |
| `Form/password_strength_theme_bootstrap5.html.twig` | Bootstrap 5 |
| `Form/password_strength_theme_bootstrap5_horizontal.html.twig` | Bootstrap 5 horizontal |
| `Form/password_strength_theme_tailwind2.html.twig` | Tailwind 2 |
| `Form/password_strength_theme_foundation5.html.twig` | Foundation 5 |
| `Form/password_strength_theme_foundation6.html.twig` | Foundation 6 |

Twig namespace: `@NowoPasswordStrengthBundle/Form/...` (registered via `TwigPathsPass`).

The themes ship **only** the bundle's own blocks (`password_strength_widget`, `password_strength_feedback`, `password_strength_generate_button_class`) and never `extends` a Symfony layout, so they cannot shadow your `form_row` / `form_label` / `form_errors` blocks wherever they sit in `twig.form_themes`. You can also pull them into your own form theme:

```twig
{# templates/form/app_theme.html.twig #}
{% use '@NowoPasswordStrengthBundle/Form/password_strength_theme_bootstrap5.html.twig' %}

{% block form_row %}...{% endblock %}
```

If you override a theme file, keep it free of `{% extends %}` for the same reason.

## Translation overrides (REQ-I18N-001)

Domain: **`NowoPasswordStrengthBundle`** (same as `translation_domain` on the form type).

The `PasswordStrength` constraint message (`password_strength.invalid`) lives in Symfony's **`validators`** domain (`src/Resources/translations/validators.<locale>.yaml`), because the validator component translates every violation message in that domain. Override it in your application's `translations/validators.<locale>.yaml`. Both domains ship the same locales (en, es, fr, de, it, pt, nl) and `make validate-translations` checks that every locale has the same keys as `en`.

**Procedure**

1. Create `translations/NowoPasswordStrengthBundle.<locale>.yaml` in your application.
2. Override only the keys you need; missing keys fall back to the bundle.

Example `translations/NowoPasswordStrengthBundle.es.yaml`:

```yaml
requirement:
  min_length: 'Al menos %count% caracteres'
  lowercase: 'Al menos una letra minúscula'
generator:
  button: 'Generar contraseña'
```

See bundle defaults in `src/Resources/translations/NowoPasswordStrengthBundle.en.yaml`.

## Demo routes

See [DEMO-FRANKENPHP.md](DEMO-FRANKENPHP.md). Examples:

- `/en/demo/level` — level presets
- `/en/demo/conditions` — inline conditions
- `/en/demo/plain` — Symfony `PasswordType` comparison

## Using it from a DDD / hexagonal application

The bundle is a **presentation** component (form field, live feedback, generator) with a stateless rules engine
behind it. It has no entities, no Doctrine mapping and no events; the domain never needs to know it exists.

- Put `PasswordStrengthType` (or `#[PasswordStrength]`) on a form DTO or command such as `RegisterUser::$plainPassword`,
  not on your `User` entity. If the constraint must live with the domain class, declare it in external validator
  mapping (`config/validator/*.yaml` or XML) so the domain class carries no Symfony attribute.
- If your domain has its own password-policy port, implement it with the bundle's evaluator port (see [ARCHITECTURE.md](ARCHITECTURE.md)); it only takes and
  returns plain values:

```php
namespace App\Infrastructure\Security;

use App\Domain\User\PasswordPolicy;                 // your port
use Nowo\PasswordStrengthBundle\Model\PasswordConditions;
use Nowo\PasswordStrengthBundle\Model\PolicyMode;
use Nowo\PasswordStrengthBundle\Service\PasswordStrengthEvaluatorInterface;
use Nowo\PasswordStrengthBundle\Service\PolicyResolverInterface;

final class NowoPasswordPolicy implements PasswordPolicy
{
    public function __construct(
        private PolicyResolverInterface $policies,
        private PasswordStrengthEvaluatorInterface $evaluator,
    ) {}

    /** @return list<string> condition keys not met (for example `min_length`), empty when acceptable */
    public function violations(string $plainPassword): array
    {
        $conditions = $this->policies->resolve(PolicyMode::Level, ['level' => 'strong']);

        return $this->evaluator->evaluate($plainPassword, $conditions)->missing;
    }
}
```

- Named profiles stored in your own database (per tenant, admin-editable) plug in through
  `Nowo\PasswordStrengthBundle\Profile\PasswordProfileProviderInterface`. The default provider
  (`ConfigPasswordProfileProvider`) reads `nowo_password_strength.profiles`; override the alias and `level` /
  `default_profile` resolve against your store, for the form field and the constraint alike:

```php
namespace App\Infrastructure\Security;

use App\Domain\Tenant\PasswordRulesRepository;    // your port
use Nowo\PasswordStrengthBundle\Model\PasswordConditions;
use Nowo\PasswordStrengthBundle\Profile\PasswordProfileProviderInterface;

final class TenantPasswordProfiles implements PasswordProfileProviderInterface
{
    public function __construct(private PasswordRulesRepository $rules) {}

    public function get(string $name): ?PasswordConditions
    {
        // Read the tenant on every call (request-scoped source); never keep it in a property: the provider is a
        // shared service, and in a worker (FrankenPHP, RoadRunner) a cached tenant would serve its policy to others.
        $rules = $this->rules->forCurrentTenant($name); // e.g. ['min_length' => 14, 'require_digit' => true]
        if (null === $rules) {
            return null;
        }

        // fromArray() ignores unknown keys: refuse a typo ('minLength') instead of silently dropping the rule.
        $unknown = PasswordConditions::unknownKeys($rules);
        if ([] !== $unknown) {
            throw new \UnexpectedValueException(sprintf('Unknown password rule key(s) "%s" in profile "%s".', implode('", "', $unknown), $name));
        }

        return PasswordConditions::fromArray($rules);
    }

    public function names(): array
    {
        return $this->rules->profileNamesForCurrentTenant();
    }
}
```

```yaml
# config/services.yaml
services:
    Nowo\PasswordStrengthBundle\Profile\PasswordProfileProviderInterface: '@App\Infrastructure\Security\TenantPasswordProfiles'
```

  Keep `get()` cheap (it runs on every form build and validation). A profile your provider returns with no rule
  (an empty row, `new PasswordConditions()`) is refused: the form field throws `InvalidOptionsException` and the
  constraint `ConstraintDefinitionException`, never accepting every password. `default_profile` must still name a
  configured or built-in profile (it is checked at container compile time against `nowo_password_strength.profiles`)
  **and** your provider must serve it; the built-in `weak` / `medium` / `strong` are not served by your provider
  unless it returns them. A one-off policy can still be built with `PasswordConditions::fromArray([...])`
  and passed to the evaluator, or to the field as inline `conditions`.

Known limitations:

- `PasswordStrengthEvaluator` and `PolicyResolver` are final classes without interfaces; keep them behind your own port.
- The conditions are serialized into the widget, so do not put secrets in `not_contain` or `regex`.

## Related bundles

- [PasswordToggleBundle](https://github.com/nowo-tech/PasswordToggleBundle) — optional show/hide toggle
