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

## Overriding bundle templates and translations

Copy templates from `src/Resources/views/Form/` to:

```
templates/bundles/PasswordStrengthBundle/Form/
```

Translations:

```
translations/PasswordStrengthBundle.en.yaml
translations/PasswordStrengthBundle.es.yaml
```

## Demo routes

See [DEMO-FRANKENPHP.md](DEMO-FRANKENPHP.md). Examples:

- `/en/demo/level` — level presets
- `/en/demo/conditions` — inline conditions
- `/en/demo/plain` — Symfony `PasswordType` comparison

## Related bundles

- [PasswordToggleBundle](https://github.com/nowo-tech/PasswordToggleBundle) — optional show/hide toggle
