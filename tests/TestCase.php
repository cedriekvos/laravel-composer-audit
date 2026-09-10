<?php

declare(strict_types=1);

namespace CedriekVos\LaravelComposerAudit\Tests;

use CedriekVos\LaravelComposerAudit\LaravelComposerAuditServiceProvider;
use Illuminate\Foundation\Application;
use Orchestra\Testbench\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * The package boots itself here exactly as it does in a host application:
     * through its own service provider, with nothing else registered for it.
     *
     * @param  Application  $app
     * @return array<int, class-string>
     */
    protected function getPackageProviders($app): array
    {
        return [LaravelComposerAuditServiceProvider::class];
    }
}
