# Usage

## Basic form field

```php
use Nowo\PasswordStrengthBundle\Form\PasswordStrengthType;

$builder->add('password', PasswordStrengthType::class, [
    'policy_mode' => 'level',
    'level' => 'medium',
]);
```

## Policy modes

### Level (preset)

```php
$builder->add('password', PasswordStrengthType::class, [
    'policy_mode' => 'level',
    'level' => 'strong', // weak | medium | strong | custom
]);
```

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

## Validator constraint

Use on entity or DTO for server-side validation (recommended alongside the form type):

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

## Overriding bundle templates (REQ-TWIG-001)

Application overrides **always win** when placed under:

```
templates/bundles/PasswordStrengthBundle/<subpath>
```

**Procedure**

1. Pick the `<subpath>` from the table below (same path as under `src/Resources/views/` in the package).
2. Create `templates/bundles/PasswordStrengthBundle/<subpath>` in your app.
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

Twig namespace: `@PasswordStrengthBundle/Form/...` (registered via `TwigPathsPass`).

## Translation overrides (REQ-I18N-001)

Domain: **`PasswordStrengthBundle`** (same as `translation_domain` on the form type).

**Procedure**

1. Create `translations/PasswordStrengthBundle.<locale>.yaml` in your application.
2. Override only the keys you need; missing keys fall back to the bundle.

Example `translations/PasswordStrengthBundle.es.yaml`:

```yaml
requirement:
  min_length: 'Al menos %count% caracteres'
  lowercase: 'Al menos una letra minúscula'
generator:
  button: 'Generar contraseña'
```

See bundle defaults in `src/Resources/translations/PasswordStrengthBundle.en.yaml`.

## Demo routes

See [DEMO-FRANKENPHP.md](DEMO-FRANKENPHP.md). Examples:

- `/en/demo/level` — level presets
- `/en/demo/conditions` — inline conditions
- `/en/demo/plain` — Symfony `PasswordType` comparison

## Related bundles

- [PasswordToggleBundle](https://github.com/nowo-tech/PasswordToggleBundle) — optional show/hide toggle
