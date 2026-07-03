# Security

## Client vs server validation

- **Live feedback** and HTML `pattern` are UX hints only. They can be bypassed in the browser.
- **Always** use the `PasswordStrength` validator (or equivalent server checks) on submit.
- The bundle does **not** store, log, or transmit passwords beyond normal form handling in your application.

## Password generator

Generated passwords are created in the browser (Web Crypto / secure random). They are not sent to third parties by this bundle.

## Dependencies

Keep Symfony, PHP, and optional [PasswordToggleBundle](https://github.com/nowo-tech/PasswordToggleBundle) updated. Report vulnerabilities per [.github/SECURITY.md](../.github/SECURITY.md).

## Reporting

See [SECURITY.md](../.github/SECURITY.md) in `.github/` for the disclosure process.
