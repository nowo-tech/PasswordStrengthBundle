# FrankenPHP worker mode audit (kernel not reset between requests)

| Field | Value |
|-------|-------|
| Package | `nowo-tech/password-strength-bundle` (`symfony-bundle`) |
| Audited revision | `v2.3.1` / release commit |
| Audit date | 2026-09-24 |
| Method | Manual review of every PHP file under `src/` (services, form type, validator, DI extension, compiler pass, config) + PHPStan `ruleset-classic.neon` + `ruleset-worker-strict.neon` |
| **Verdict** | ✅ **Viable** — all services are stateless or `readonly`; safe with or without kernel reset (`FRANKENPHP_RESET_KERNEL` unset / `false`) |

## Execution model assumed

FrankenPHP worker mode boots the Symfony kernel once per worker and serves many requests with the same container. This audit assumes the **strict** variant: the kernel is **not** rebooted between requests, so every shared service, static property and PHP global survives from one request to the next. Two scenarios are evaluated:

- **A — kernel not rebooted, `services_resetter` still runs:** services tagged `kernel.reset` (or implementing `ResetInterface`) are reset between requests.
- **B — no reset at all:** nothing is reset; any per-request state kept in a service leaks into the next request.

A bundle that is safe under **B** is safe under **A** and under classic mode / PHP-FPM.

## Summary

| Area | Status | Notes |
|------|--------|-------|
| Mutable state in shared services | ✅ | `PolicyResolver`, `PasswordStrengthEvaluator`, `PasswordPatternBuilder` are `final readonly`; `PasswordStrengthType` only has `readonly` constructor properties |
| Static properties / `static` locals | ✅ | `ParentFormTypeResolver` and `PasswordToggleIntegration` only expose pure static methods; no static properties |
| `ResetInterface` / `kernel.reset` coverage | ✅ N/A | Nothing to reset |
| Request / user / locale captured in services | ✅ | Password and options are passed as method arguments; no `RequestStack` / `TokenStorage` dependency |
| Superglobals, `$_ENV`, `putenv`, `ini_set`, `setlocale`, timezone | ✅ | None used; config is compiled into container parameters |
| Doctrine / EntityManager | ✅ N/A | No persistence |
| Output, headers, `exit`, shutdown functions | ✅ | None |
| Resources (files, sockets, cURL) held open | ✅ | None at runtime (`is_dir()` only in the compiler pass, at container build time) |
| Memory growth across requests | ✅ | No caches or accumulating arrays |
| Blocking I/O and timeouts | ✅ N/A | No I/O at runtime |
| Third-party static state | ✅ | Only Symfony Form / Validator / DI |
| PHPStan FrankenPHP rulesets | ✅ | `ruleset-classic.neon` + `ruleset-worker-strict.neon` in `phpstan.neon.dist` |

## Services reviewed

| Service | Shared | Mutable state | Scenario A | Scenario B |
|---------|--------|---------------|------------|------------|
| `Nowo\PasswordStrengthBundle\Service\PolicyResolver` | yes | none (`readonly` `$levels` from config) | ✅ | ✅ |
| `Nowo\PasswordStrengthBundle\Service\PasswordStrengthEvaluator` | yes | none (`readonly`, only the pattern builder) | ✅ | ✅ |
| `Nowo\PasswordStrengthBundle\Service\PasswordPatternBuilder` | yes | none (`readonly`, no properties) | ✅ | ✅ |
| `Nowo\PasswordStrengthBundle\Form\PasswordStrengthType` (`form.type`) | yes | none (`readonly` defaults from config) | ✅ | ✅ |
| `Nowo\PasswordStrengthBundle\Validator\PasswordStrengthValidator` (`validator.constraint_validator`) | yes | inherited `ConstraintValidator::$context`, re-initialized by the validator before every `validate()` call | ✅ | ✅ |
| `Integration\ParentFormTypeResolver`, `Integration\PasswordToggleIntegration` | registered by the `resource: '../../*'` glob, used statically | none (pure static helpers, `class_exists()` checks) | ✅ | ✅ |

Value objects under `src/Model/` (`PasswordConditions`, `EvaluationResult`, `RequirementResult`, enums) are excluded from the container (`src/Resources/config/services.yaml`), created per call and never stored in a service.

## Findings

No findings above Info.

### W-01 — Validator context is framework-managed (Info)

- **Where:** `src/Validator/PasswordStrengthValidator.php` extends `ConstraintValidator`; it uses `$this->context`.
- **Worker impact:** `$context` is the only mutable property. Symfony's `ExecutionContext` calls `initialize()` before each validation, so the previous request's context is overwritten before it is read. The previous context object stays referenced until the next validation, which is a negligible memory cost. No data is exposed across requests.
- **Recommendation:** none. Keep custom validators that extend this class free of extra per-call properties.

### W-02 — Parent form type resolved at container build time (Info)

- **Where:** `src/DependencyInjection/PasswordStrengthExtension.php`; `src/Integration/PasswordToggleIntegration.php`.
- **Worker impact:** the choice between Symfony `PasswordType` and PasswordToggleBundle's type is compiled into the `nowo_password_strength.parent_form_type` parameter. Installing or removing PasswordToggleBundle requires a cache clear and a worker restart, as with any container change. At runtime, `isToggleFormType()` only runs `class_exists()` on an autoloaded class, which is cheap and deterministic.
- **Recommendation:** restart workers after `composer require/remove nowo-tech/password-toggle-bundle`.

## Usage recommendations in worker mode

- No special configuration or reset hook is needed when `FRANKENPHP_RESET_KERNEL` is unset or `false`.
- Custom `levels` and inline `conditions` regexes are compiled by PCRE and cached per process (bounded by PHP's PCRE cache). Avoid building `conditions.regex` from user input per request.
- Subclasses or decorators of `PolicyResolver` / `PasswordStrengthEvaluator` must stay stateless (or implement `ResetInterface`) to keep this verdict.
- The demo (`demo/symfony8/docker/frankenphp/Caddyfile`) already runs FrankenPHP in `worker` mode.

## Re-audit triggers

Re-run this audit when a change adds: properties to any service in `src/Service/`, `src/Form/` or `src/Validator/`; a policy or evaluation cache; a dependency on `RequestStack`, `TokenStorage` or the current locale; an event listener or Twig extension; or any use of `$_SERVER` / `$_ENV` at runtime.
