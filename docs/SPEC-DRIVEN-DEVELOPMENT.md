# Spec-driven development

In this repository, **spec-driven development** has two layers that stay in sync:

1. **Product behavior** — what **Password Strength Bundle** guarantees (form type, validator, policies, live feedback). See [`USAGE.md`](USAGE.md) and [`CONFIGURATION.md`](CONFIGURATION.md); **PHPUnit**, **Vitest**, and **PHPStan** enforce it in CI.
2. **Traceability anchors** — stable **`REQ-*`** identifiers from the Nowo bundle checklist (`BUNDLES_FULL_SPECS_DETAILS.md`) in Makefiles and demos.

There is no separate executable spec language (Gherkin); tests and static analysis are the mechanical proof.

---

## User stories

| ID | Story |
| --- | --- |
| US-01 | **As a** Symfony integrator, **I want** `PasswordStrengthType` **so that** password fields show live requirements and HTML `pattern` from my policy. |
| US-02 | **As a** integrator, **I want** `policy_mode: level` with configurable presets **so that** I reuse weak/medium/strong without duplicating YAML. |
| US-03 | **As a** integrator, **I want** inline `conditions` **so that** I define one-off policies per field. |
| US-04 | **As a** integrator, **I want** the `PasswordStrength` validator **so that** passwords are enforced server-side even if JavaScript is disabled. |
| US-05 | **As a** integrator, **I want** optional password generator modes **so that** users can generate compliant passwords in the browser. |
| US-06 | **As a** integrator, **I want** optional PasswordToggleBundle integration **so that** show/hide works alongside strength UI. |

**Non-goals:** storing passwords, password hashing, account lockout, or breach database checks (use dedicated security bundles / application logic).

---

## Bundle functional scope

**In scope:** `PasswordStrengthType`, `PasswordStrength` constraint, policy resolver/evaluator, pattern builder, TypeScript IIFE, Twig form themes, translations (EN/ES), Flex recipe, Symfony 8 FrankenPHP demo.

**Out of scope for the Packagist API:** demo applications, Docker tooling, Cursor rules.

---

## Validating the functional spec

```bash
make qa              # cs-check + PHPUnit
make test-coverage   # PHPUnit + PHP line coverage report
make test-ts         # Vitest + TS coverage report
make release-check   # full pre-release pipeline
```

Behavior changes require tests under `tests/` and/or `src/Resources/assets/**/*.test.ts`.

---

## Requirement identifiers (`REQ-*`)

| ID | Where | What it marks |
| --- | --- | --- |
| `REQ-MAKE-001` | Root [`Makefile`](../Makefile) | Docker dev workflow, `release-check` |
| `REQ-MAKE-004` | Root `Makefile` | `validate-translations` |
| `REQ-MAKE-008` | Root `Makefile` | `update-deps` via shared script include |
| `REQ-DEMO-005` | [`demo/symfony8/Makefile`](../demo/symfony8/Makefile) | `make up` prints `Demo started at: http://localhost:<PORT>` |
| `REQ-DEMO-007` | [`demo/Makefile`](../demo/Makefile), demo Makefiles | `update-bundle` syncs path-repo bundle + cache |
| `REQ-PS-001`…`007` | Product scope | Policy modes, validator, feedback, generator, themes, toggle integration |

When changing scripted behavior, update the matching `REQ-*` comment or add a new ID and reference it in the PR.

---

## Contributor workflow

1. Clarify behavior (user story / issue).
2. Implement with PHPUnit and/or Vitest coverage.
3. Update integrator docs (`USAGE`, `CONFIGURATION`, `CHANGELOG`) when behavior or config changes.
4. Run `make release-check` before tagging.

---

## Relationship to Engram

Org-wide documentation hygiene and MCP setup: [`ENGRAM.md`](ENGRAM.md). This file defines **bundle behavior** and local `REQ-*` traceability.

---

## See also

- [Usage](USAGE.md)
- [Configuration](CONFIGURATION.md)
- [Contributing](CONTRIBUTING.md)
- [Release](RELEASE.md)
- [Demo with FrankenPHP](DEMO-FRANKENPHP.md)
