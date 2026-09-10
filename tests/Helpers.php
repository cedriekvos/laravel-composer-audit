<?php

use CedriekVos\LaravelComposerAudit\Mail\ComposerVulnerabilityAlert;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;

/*
 * The steps the feature tests are written in. They reach the package only at
 * its edges — what `composer audit` answers, the artisan command the scheduler
 * calls, and the mail that goes out — so the scenarios hold however the mute
 * state is stored. Pest loads this file before any test file.
 */

/**
 * Background: an alert recipient address is configured. Mail and the mute
 * state disk are faked, a real `composer audit` never runs, and time stands
 * still so "hours ago" is exact.
 *
 * Pest attributes the hook to the test file calling this, as it does for uses().
 */
function usesFakeVulnerabilityCheck(): void
{
    beforeEach(function () {
        Storage::fake('laravel-composer-audit');
        Mail::fake();
        Process::preventStrayProcesses();
        Carbon::setTestNow(Carbon::create(2026, 6, 9, 12, 0, 0));
        config(['laravel-composer-audit.alert_recipient' => 'security@example.test']);
    });
}

/**
 * Given the dependency audit reports these vulnerabilities, in the shape
 * `composer audit --format=json` reports them. Composer exits 1 when it finds
 * advisories, which the check must not mistake for an audit that failed.
 *
 * @param  array<int, array{package: string, advisory: string, title?: string, severity?: string}>  $vulnerabilities
 */
function fakeComposerAudit(array $vulnerabilities): void
{
    $advisories = [];

    foreach ($vulnerabilities as $vulnerability) {
        $advisories[$vulnerability['package']][] = [
            'advisoryId' => $vulnerability['advisory'],
            'packageName' => $vulnerability['package'],
            'affectedVersions' => '<1.0.0',
            'title' => $vulnerability['title'] ?? 'Security advisory',
            'cve' => null,
            'link' => 'https://github.com/advisories/'.$vulnerability['advisory'],
            'reportedAt' => '2026-06-01T00:00:00+00:00',
            'sources' => [['name' => 'GitHub', 'remoteId' => $vulnerability['advisory']]],
            'severity' => $vulnerability['severity'] ?? 'high',
        ];
    }

    Process::fake([
        'composer audit *' => Process::result(
            output: json_encode(['advisories' => $advisories, 'abandoned' => []]),
            exitCode: $advisories === [] ? 0 : 1,
        ),
    ]);
}

/**
 * Given the dependency audit cannot be run: it produces no report at all, as
 * when the composer binary is missing.
 */
function fakeFailingComposerAudit(): void
{
    Process::fake([
        'composer audit *' => Process::result(errorOutput: 'composer: command not found', exitCode: 127),
    ]);
}

/**
 * When the vulnerability check runs, through the command the scheduler calls.
 */
function runVulnerabilityCheck(): void
{
    Artisan::call('security:check-vulnerabilities');
}

/**
 * Given the vulnerability was reported the given number of hours ago: a run
 * back then found it and alerted about it. That alert is discarded afterwards,
 * so the test only sees what the run under test sends.
 */
function reportVulnerability(string $package, string $advisory, int $hoursAgo): void
{
    Carbon::withTestNow(Carbon::now()->subHours($hoursAgo), function () use ($package, $advisory) {
        fakeComposerAudit([['package' => $package, 'advisory' => $advisory]]);
        runVulnerabilityCheck();
    });

    Mail::assertSent(ComposerVulnerabilityAlert::class, 1);
    Mail::fake();
}

/**
 * Given a later audit reported the vulnerability as resolved: a run the given
 * number of hours ago found the audit clean.
 */
function reportVulnerabilityResolved(int $hoursAgo): void
{
    Carbon::withTestNow(Carbon::now()->subHours($hoursAgo), function () {
        fakeComposerAudit([]);
        runVulnerabilityCheck();
    });
}
