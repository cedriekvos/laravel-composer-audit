# Laravel Composer Audit

> [!WARNING]
> This project is built to fit my own personal monitoring setup, not as a supported product. Treat it as
> a reference rather than a dependency: I may make breaking changes at any time, without notice or
> migration notes.

Checks your Laravel application's installed Composer packages for known security vulnerabilities every
hour, and emails a maintainer when one turns up.

`composer audit` can already compare your installed packages against published security advisories,
but it only helps if someone runs it and reads the output. This package runs it on the scheduler and
sends one email per new finding. A vulnerability you can't fix straight away is reported again every
48 hours, not every hour.

## Requirements

- PHP 8.4
- Laravel 13.8 or newer
- The `composer` binary on the `PATH` of the server that runs the scheduler, with network access so
  Composer can fetch the advisories
- A working mailer

## Installation

The package isn't on Packagist, so Composer has to be told to fetch it from GitHub. In your
application, add the repository:

```bash
composer config repositories.laravel-composer-audit vcs https://github.com/cedriekvos/laravel-composer-audit
```

Or add it to your application's `composer.json` by hand:

```json
"repositories": [
    {
        "type": "vcs",
        "url": "https://github.com/cedriekvos/laravel-composer-audit"
    }
]
```

Then require the package. Composer picks the latest tagged release:

```bash
composer require cedriekvos/laravel-composer-audit
```

Your application's `composer.lock` records the exact version that was installed, and you only get newer
releases when you run `composer update`. Read the [changelog](CHANGELOG.md) before you do, because
breaking changes can land without notice.

If the repository is private, or Composer runs into GitHub's API rate limit, it asks you for a GitHub
personal access token.

Laravel discovers the service provider automatically. Set the address that should receive alerts in
your `.env`:

```dotenv
LARAVEL_COMPOSER_AUDIT_ALERT_RECIPIENT=security@example.com
```

The package schedules itself, so make sure the Laravel scheduler is running:

```cron
* * * * * cd /path-to-your-project && php artisan schedule:run >> /dev/null 2>&1
```

That's all. The check runs at the start of every hour.

## How it works

Each run of `security:check-vulnerabilities`:

1. runs `composer audit --format=json` in your application's root, which checks the packages installed
   in `vendor/`;
2. works out which of the reported vulnerabilities are due for an alert;
3. sends one email listing them to the configured recipient;
4. records which vulnerabilities it has reported.

A vulnerability is identified by its advisory ID together with the package it affects. These rules
decide what gets reported:

| Situation | What happens |
|---|---|
| A vulnerability shows up for the first time | Reported in the next alert |
| Several new vulnerabilities in the same run | Bundled into a single email |
| A reported vulnerability is still present | Muted for 48 hours, then reported again (exactly 48 hours counts as due) |
| New and muted vulnerabilities in the same run | The email lists only the ones that are due |
| A vulnerability disappears and later comes back | Treated as new and reported straight away |
| The audit finds nothing | No email. There is no "all clear" message |
| No recipient is configured | No email, and nothing is recorded as reported, so the vulnerability is reported once a recipient is set |
| The audit can't run (no `composer` binary, output isn't JSON) | The command throws, the scheduled task fails, and the recorded state is left as it was |
| Sending the alert fails | The command throws the mailer's error, and nothing is recorded as reported, so the next run tries again |

Every severity is reported. There is no threshold, because you're the best judge of what's urgent in
your application.

The email lists the package, advisory ID, title and severity of each vulnerability. So that you can tell
alerts apart when the package runs in several projects, the subject starts with your `APP_NAME`, as in
**[My Blog] Composer vulnerability alert**, and the body names the application by its `APP_URL`. It is
sent from your application's default `mail.from` address, directly during the check rather than through
the queue, so no queue worker is needed.

### Running the check by hand

```bash
php artisan security:check-vulnerabilities
```

This runs the real check: it emails the recipient and records the result, exactly like a scheduled
run.

## Configuration

The only setting is the recipient address, read from `LARAVEL_COMPOSER_AUDIT_ALERT_RECIPIENT`. It takes
a single address; use a mailing list or group address to reach several people. You only need to
publish the config file if you want to set the address some other way:

```bash
php artisan vendor:publish --tag=laravel-composer-audit-config
```

### Customising the email

```bash
php artisan vendor:publish --tag=laravel-composer-audit-views
```

This copies the template to
`resources/views/vendor/laravel-composer-audit/mail/composer-vulnerability-alert.blade.php`. It receives
`$vulnerabilities`: a list of objects with `package`, `advisory`, `title` and `severity` properties.

### Where the mute state is stored

The package keeps track of what it has reported in `vulnerability-mutes.json`, on a filesystem disk
named `laravel-composer-audit`. Unless you define that disk yourself, it is a local disk rooted at
`storage/app/private/laravel-composer-audit`.

If your storage directory is wiped on every deploy (in containers, for example), the package forgets
what it has reported and alerts again after each deploy. To avoid that, define a
`laravel-composer-audit` disk in `config/filesystems.php` that points at persistent storage. The package
uses your definition instead of its own.

Run the scheduler on a single server. Every server that runs it performs its own check and sends its
own alert.

## Documentation

- [`documentation/features/security/composer_vulnerability_alert.feature`](documentation/features/security/composer_vulnerability_alert.feature):
  the behaviour, scenario by scenario. Each scenario is a test in
  [`tests/Features/Security/ComposerVulnerabilityAlertTest.php`](tests/Features/Security/ComposerVulnerabilityAlertTest.php).
- [`documentation/leesmij/security/composer_vulnerability_alert.md`](documentation/leesmij/security/composer_vulnerability_alert.md):
  why it behaves this way (in Dutch).

## Development

```bash
composer install
composer test     # run the test suite
composer qa       # composer validate, Rector, Pint, PHPStan (level 10), and Pest at 100% coverage,
                  # type coverage and mutation score
composer fix-qa   # apply Rector and Pint fixes
```

`composer qa` needs a code coverage driver (PCOV or Xdebug) for its coverage and mutation steps.

## License

MIT. See [`LICENSE`](LICENSE).
