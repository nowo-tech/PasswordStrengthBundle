# Architecture

`nowo-tech/password-strength-bundle` is a presentation bundle (form field, constraint, live feedback) over a stateless
rules engine. It has no entities, no Doctrine mapping and no events.

## Table of contents

- [Public ports](#public-ports)
- [Extension points (SPI)](#extension-points-spi)
- [Standard Symfony and DDD hosts](#standard-symfony-and-ddd-hosts)

## Public ports

| Port | Default implementation | Purpose |
|------|------------------------|---------|
| `Service\PolicyResolverInterface` | `Service\PolicyResolver` | Resolves the effective `PasswordConditions` of a field or constraint (named profile or inline conditions). |
| `Service\PasswordStrengthEvaluatorInterface` | `Service\PasswordStrengthEvaluator` | Evaluates a password against `PasswordConditions`; returns an `EvaluationResult` VO. |
| `Service\PasswordPatternBuilderInterface` | `Service\PasswordPatternBuilder` | Builds the HTML `pattern` for a set of conditions. |
| `Profile\PasswordProfileProviderInterface` | `Profile\ConfigPasswordProfileProvider` | Source of the named profiles. |

Every port takes and returns scalars and bundle value objects (`PasswordConditions`, `EvaluationResult`,
`RequirementResult`, `PolicyMode`); no Symfony, Doctrine or HTTP type appears in them. Each interface is aliased to
its default service, so autowiring by interface and by the old class id both work.

## Extension points (SPI)

Replace any port by pointing its alias to your own service:

```yaml
services:
  App\Security\StrictEvaluator: ~
  Nowo\PasswordStrengthBundle\Service\PasswordStrengthEvaluatorInterface:
    alias: App\Security\StrictEvaluator
```

The form type and the validator depend on the interfaces, so your implementation is used by both. The service
holds no per-request state; keep yours stateless as well (worker mode, kernel not reset).

## Standard Symfony and DDD hosts

- **Standard Symfony host:** zero configuration. Use `PasswordStrengthType` or `#[PasswordStrength]`; the defaults are
  wired and every port is autowirable by interface.
- **DDD / hexagonal host:** the bundle has nothing to map and nothing to extend. Keep the constraint on a form DTO or
  command (or in external validator mapping), and implement your domain's password-policy port with the ports above
  (see "Using it from a DDD / hexagonal application" in [USAGE.md](USAGE.md)). Named profiles from your own store plug
  in through `PasswordProfileProviderInterface`, and a whole port can be swapped through its alias.
