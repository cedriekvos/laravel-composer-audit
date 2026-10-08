# Changelog

Notable changes to this project are listed in this file. The format is based on
[Keep a Changelog](https://keepachangelog.com/en/1.1.0/).

## [Unreleased]

### Changed

- The alert email names the application it is about, so alerts can be told apart when the package runs
  in several projects. The subject is now `[<APP_NAME>] Composer vulnerability alert`, and the body
  mentions the `APP_URL`. Update mail filters that match the old subject exactly. If you published the
  email template, add `{{ config('app.url') }}` to your copy.

## [1.0.0] - 2026-09-10

First release.

### Added

- The `security:check-vulnerabilities` command, scheduled to run every hour. It runs `composer audit`
  and emails the configured recipient about vulnerabilities in installed packages.
- One alert per run, listing the package, advisory ID, title and severity of each vulnerability.
- A 48-hour mute window. A vulnerability that is still present is reported again once 48 hours have
  passed since its last alert. One that disappears and comes back is reported straight away.
- The `LARAVEL_COMPOSER_AUDIT_ALERT_RECIPIENT` setting for the recipient. Without it, no email is sent
  and nothing is recorded as reported.
- Loud failures. An audit that can't run, or an alert that can't be sent, fails the command and
  leaves the recorded state as it was.
- A publishable config file and email template, and a `laravel-composer-audit` filesystem disk for
  the mute state that your application can override.

[unreleased]: https://github.com/cedriekvos/laravel-composer-audit/compare/v1.0.0...HEAD
[1.0.0]: https://github.com/cedriekvos/laravel-composer-audit/releases/tag/v1.0.0
