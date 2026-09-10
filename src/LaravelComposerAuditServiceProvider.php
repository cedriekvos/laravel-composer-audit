<?php

declare(strict_types=1);

namespace CedriekVos\LaravelComposerAudit;

use CedriekVos\LaravelComposerAudit\Console\Commands\CheckComposerVulnerabilitiesCommand;
use CedriekVos\LaravelComposerAudit\Storage\MuteFileStorage;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\ServiceProvider;

final class LaravelComposerAuditServiceProvider extends ServiceProvider
{
    private const string NAME = 'laravel-composer-audit';

    public function register(): void
    {
        $this->mergeConfigFrom($this->configPath(), self::NAME);
    }

    public function boot(): void
    {
        $this->registerMuteStateDisk();

        $this->loadViewsFrom($this->viewsPath(), self::NAME);
        $this->commands([CheckComposerVulnerabilitiesCommand::class]);

        $this->publishes([$this->configPath() => config_path(self::NAME.'.php')], self::NAME.'-config');
        $this->publishes([$this->viewsPath() => resource_path('views/vendor/'.self::NAME)], self::NAME.'-views');

        $this->callAfterResolving(Schedule::class, function (Schedule $schedule): void {
            $schedule->command(CheckComposerVulnerabilitiesCommand::class)->hourly();
        });
    }

    /**
     * Register the local disk the 48-hour mute state lives on, so the package
     * brings its own storage rather than relying on the host application to
     * configure one. A disk the host has already defined under that name wins.
     */
    private function registerMuteStateDisk(): void
    {
        $key = 'filesystems.disks.'.MuteFileStorage::DISK;

        if (Config::get($key) !== null) {
            return;
        }

        Config::set($key, [
            'driver' => 'local',
            'root' => storage_path('app/private/'.MuteFileStorage::DISK),
            'throw' => false,
            'report' => false,
        ]);
    }

    private function configPath(): string
    {
        return __DIR__.'/../config/'.self::NAME.'.php';
    }

    private function viewsPath(): string
    {
        return __DIR__.'/../resources/views';
    }
}
