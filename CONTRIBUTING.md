# Contributing to csuite-crm

Thank you for your interest in contributing. This project is MIT-licensed and welcomes pull requests.

## Getting started

1. Fork the repository
2. Create a branch: `feature/your-feature-name` or `i18n/language-name`
3. Make your changes
4. Submit a pull request

## Code style

- **PHP** — PSR-12 naming conventions. No frameworks, no Composer, no namespaces.
- **Comments** — British English. Add a comment only when the why is non-obvious.
- **SQL** — PDO prepared statements only. No string interpolation.
- **HTML output** — `htmlspecialchars()` or the `e()` helper on all user-supplied values.
- **UI strings** — all user-facing text must go through `__()`. No hardcoded English or Spanish strings in templates.

## Adding a language

Follow the instructions in [lang/README.md](lang/README.md). Translate naturally — avoid literal machine translation.

Pull request title format: `i18n: add French translation`

## Testing

Before submitting a pull request, manually test:

- All CRUD operations (contacts, notes, tasks)
- All six agent roles with at least one prompt
- Language switching (EN → ES and back)
- Login, logout, and session expiry
- Delete confirmation and GDPR hard delete

There is no automated test suite. Test on PHP 8.1+ and MySQL 8.0+.

## Reporting bugs

Use GitHub Issues. Bug reports must include:

- PHP version (`php -v`)
- MySQL version (`mysql --version`)
- Steps to reproduce
- Expected vs actual behaviour
- Any relevant error messages from the PHP error log

## Licence

All contributions are MIT-licensed. By submitting a pull request you agree that your contribution will be released under the project's MIT licence.
