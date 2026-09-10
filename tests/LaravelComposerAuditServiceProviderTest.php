<?php

use CedriekVos\LaravelComposerAudit\Console\Commands\CheckComposerVulnerabilitiesCommand;
use CedriekVos\LaravelComposerAudit\LaravelComposerAuditServiceProvider;
use CedriekVos\LaravelComposerAudit\Storage\MuteFileStorage;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

covers(LaravelComposerAuditServiceProvider::class);

it('merges the package configuration so the host needs no config file', function () {
    expect(config('laravel-composer-audit'))->toHaveKey('alert_recipient');
});

it('registers the mute state disk under private storage', function () {
    expect(config('filesystems.disks.'.MuteFileStorage::DISK))->toBe([
        'driver' => 'local',
        'root' => storage_path('app/private/'.MuteFileStorage::DISK),
        'throw' => false,
        'report' => false,
    ]);
});

it('leaves a mute state disk the host application configured itself untouched', function () {
    config(['filesystems.disks.'.MuteFileStorage::DISK => ['driver' => 's3']]);

    (new LaravelComposerAuditServiceProvider(app()))->boot();

    expect(config('filesystems.disks.'.MuteFileStorage::DISK))->toBe(['driver' => 's3']);
});

it('registers the vulnerability check command with artisan', function () {
    expect(Artisan::all())->toHaveKey('security:check-vulnerabilities')
        ->and(Artisan::all()['security:check-vulnerabilities'])->toBeInstanceOf(CheckComposerVulnerabilitiesCommand::class);
});

it('schedules the vulnerability check to run every hour', function () {
    $event = collect(app(Schedule::class)->events())
        ->first(fn (mixed $event): bool => str_contains((string) $event->command, 'security:check-vulnerabilities'));

    expect($event)->not->toBeNull()
        ->and($event->expression)->toBe('0 * * * *');
});

it('registers the alert view under the package namespace', function () {
    expect(View::exists('laravel-composer-audit::mail.composer-vulnerability-alert'))->toBeTrue();
});

it('publishes its config and views', function () {
    expect(array_values(ServiceProvider::pathsToPublish(LaravelComposerAuditServiceProvider::class, 'laravel-composer-audit-config')))
        ->toBe([config_path('laravel-composer-audit.php')])
        ->and(array_values(ServiceProvider::pathsToPublish(LaravelComposerAuditServiceProvider::class, 'laravel-composer-audit-views')))
        ->toBe([resource_path('views/vendor/laravel-composer-audit')]);
});
