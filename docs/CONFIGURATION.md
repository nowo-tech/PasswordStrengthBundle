# Configuration

Configuration lives under the `nowo_password_strength` key in `config/packages/nowo_password_strength.yaml`.

## Full example

```yaml
nowo_password_strength:
  form_theme: bootstrap_5_layout.html.twig
  feedback_position: below
  show_requirements: true
  live_feedback: true
  default_level: medium
  generator_mode: off
  generator_count: 3
  use_password_toggle: true
  # parent_form_type: ~   # null = auto-detect parent PasswordType
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
```

## Options

| Option | Type | Default | Description |
|--------|------|---------|-------------|
| `form_theme` | string | `form_div_layout.html.twig` | Base Symfony form layout; must match your app's `twig.form_themes`. |
| `feedback_position` | `above` \| `below` | `below` | Where to show the live requirements list. |
| `show_requirements` | bool | `true` | Show requirement checklist in the UI. |
| `live_feedback` | bool | `true` | Update requirements while typing (client-side). |
| `default_level` | string | `medium` | Default level when using `policy_mode: level`. |
| `generator_mode` | `off` \| `input` \| `modal` | `off` | Default password generator behaviour. |
| `generator_count` | int (1–10) | `3` | Suggestions count in modal mode. |
| `use_password_toggle` | bool | `true` | Use PasswordToggleBundle parent when installed. |
| `parent_form_type` | string \| null | `null` | Force parent form type FQCN; `null` = auto. |
| `levels` | map | weak/medium/strong | Named level presets for `policy_mode: level`. |

## Condition keys (levels or inline `conditions`)

| Key | Type | Description |
|-----|------|-------------|
| `min_length` | int | Minimum length |
| `max_length` | int | Maximum length |
| `require_lowercase` | bool | At least one `a-z` |
| `require_uppercase` | bool | At least one `A-Z` |
| `require_digit` | bool | At least one digit |
| `require_special` | bool | At least one special character |
| `special_chars` | string | Charset for special chars |
| `disallow_whitespace` | bool | No spaces |
| `not_contain` | string[] | Forbidden substrings |
| `min_unique_chars` | int | Minimum unique characters |
| `regex` | string | Custom regex (server + pattern) |

## `form_theme`

The bundle maps `form_theme` to a dedicated Twig theme that extends your layout and adds `password_strength_widget`. Supported values include:

- `form_div_layout.html.twig`, `form_table_layout.html.twig`
- `bootstrap_3_layout.html.twig`, `bootstrap_4_layout.html.twig`, `bootstrap_5_layout.html.twig` (and horizontal variants)
- `tailwind_2_layout.html.twig`
- `foundation_5_layout.html.twig`, `foundation_6_layout.html.twig`

## PasswordToggleBundle

When `use_password_toggle: true` and PasswordToggleBundle is installed, the toggle form theme is prepended automatically. Set `use_password_toggle: false` or `parent_form_type: Symfony\Component\Form\Extension\Core\Type\PasswordType` to disable.
