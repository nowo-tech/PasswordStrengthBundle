# Configuration

Configuration lives under the `nowo_password_strength` key in `config/packages/nowo_password_strength.yaml`.


## Table of contents

- [Full example](#full-example)
- [Options](#options)
- [Profiles](#profiles)
- [Condition keys (profiles or inline `conditions`)](#condition-keys-profiles-or-inline-conditions)
- [`form_theme`](#form_theme)
- [PasswordToggleBundle](#passwordtogglebundle)

## Full example

```yaml
nowo_password_strength:
  form_theme: bootstrap_5_layout.html.twig
  feedback_position: below
  show_requirements: true
  live_feedback: true
  default_profile: medium
  generator_mode: off
  generator_count: 3
  use_password_toggle: true
  csp_nonce_attribute: csp_nonce   # request attribute with the CSP nonce; '' disables (2.6+)
  ui_translation_domain: null      # domain of the widget copy; null = the field translation_domain (2.8+)
  # parent_form_type: ~   # null = auto-detect parent PasswordType
  profiles:                        # weak / medium / strong are built in; list only the ones you add or override
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
| `default_profile` | string | `medium` | (formerly `default_level`) Profile used when using `policy_mode: level` without an explicit `level` (form type `level` option default, and the `PasswordStrength` constraint when `level` is null). Must exist under `profiles`. |
| `generator_mode` | `off` \| `input` \| `modal` | `off` | Default password generator behaviour. |
| `generator_count` | int (1–10) | `3` | Suggestions count in modal mode. |
| `use_password_toggle` | bool | `true` | Use PasswordToggleBundle parent when installed. |
| `parent_form_type` | string \| null | `null` | Force parent form type FQCN; `null` = auto. |
| `ui_translation_domain` | string\|null | `null` | (2.8+) Translation domain of the widget copy (requirements, generator, toggle labels) — also a form option. `null` = the field `translation_domain` (default `NowoPasswordStrengthBundle`). Set `NowoPasswordStrengthBundle` when your forms translate labels in another domain (e.g. FormKit's `form`), instead of overriding the widget. |
| `csp_nonce_attribute` | string | `csp_nonce` | (2.6+) Request attribute holding the per-request CSP nonce (nowo-tech convention, set by HttpHardeningBundle / UiKit hosts). The form type reads it on every render (current request, then main request; nothing kept between requests) and stamps it as `data-nonce` on `<nowo-password-strength>` and `data-password-strength-nonce` on the input, so `password-strength.js` nonces the base `<style>` it injects — also when a host theme copies the widget block. `''` disables. |
| `profiles` | map | weak/medium/strong | (formerly `levels`) Named condition profiles for `policy_mode: level`. See [Profiles](#profiles). |

## Profiles

Named condition sets follow the nowo-tech `default_profile` + `profiles` convention (REQ-CFG-001). Each profile is a complete set of [condition keys](#condition-keys-profiles-or-inline-conditions).

```yaml
nowo_password_strength:
  default_profile: standard
  profiles:
    standard:
      min_length: 10
      require_lowercase: true
      require_uppercase: true
      require_digit: true
    admin:
      min_length: 16
      require_lowercase: true
      require_uppercase: true
      require_digit: true
      require_special: true
      min_unique_chars: 8
```

- **Built-in profiles.** `weak`, `medium` and `strong` (values in the [full example](#full-example)) are always available. A profile you configure with one of those names replaces the built-in one entirely; any other name is added next to them. The example above therefore ends up with `weak`, `medium`, `strong`, `standard` and `admin`.
- **Default profile.** `default_profile` (default `medium`) MUST name an existing profile — built-in or configured. An unknown or empty name fails at container compile (`cache:clear`) with `default_profile "…" must exist under nowo_password_strength.profiles (available: …)`.
- **Runtime selection.** The profile is picked per field or per constraint, by name, through the `level` option (kept for backward compatibility):
  - form type: `PasswordStrengthType` with `'policy_mode' => 'level', 'level' => 'admin'`;
  - constraint: `#[PasswordStrength(level: 'admin')]`.

  When `level` is omitted or `null`, `default_profile` applies. An unknown `level` name also falls back to `default_profile` (it never disables the policy). `policy_mode: conditions` ignores profiles and uses the inline `conditions` option.
- **Legacy keys.** The former names are still accepted and normalized onto the canonical keys: `levels` → `profiles`, `default_level` → `default_profile`. Setting both the legacy and the canonical key in the same file is a configuration error. The container parameters `nowo_password_strength.levels` and `nowo_password_strength.default_level` remain as aliases of `nowo_password_strength.profiles` / `nowo_password_strength.default_profile`.
- **Container parameters.** `nowo_password_strength.profiles` (the merged map, built-ins included) and `nowo_password_strength.default_profile`.

There is no legacy flat form: conditions always live inside a named profile.

## Condition keys (profiles or inline `conditions`)

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
| `regex` | string | Custom regex, bare (`^[A-Z]+$`) or delimited (`/^[a-z]+$/i`); checked server + client as given. The HTML `pattern` uses the delimiter-free body and is omitted when flags other than `u` are set. |

## `form_theme`

The bundle maps `form_theme` to a dedicated Twig theme that adds `password_strength_widget` (and its feedback / generator blocks) on top of your layout. The theme does not extend any layout, so it never shadows your own `form_row` / `form_label` / `form_errors` blocks. Supported values include:

- `form_div_layout.html.twig`, `form_table_layout.html.twig`
- `bootstrap_3_layout.html.twig`, `bootstrap_4_layout.html.twig`, `bootstrap_5_layout.html.twig` (and horizontal variants)
- `tailwind_2_layout.html.twig`
- `foundation_5_layout.html.twig`, `foundation_6_layout.html.twig`

## PasswordToggleBundle

When `use_password_toggle: true` and PasswordToggleBundle is installed, the toggle form theme is prepended automatically. Set `use_password_toggle: false` or `parent_form_type: Symfony\Component\Form\Extension\Core\Type\PasswordType` to disable.
