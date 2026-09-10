<?php

use Illuminate\Console\Command;
use Illuminate\Mail\Mailable;

/*
 * The rules the code carried as App\Security, kept now that it stands on its own:
 * a file-backed domain that knows nothing about HTTP or the database, entered
 * through one console command and leaving through one mailable.
 */

$domain = 'CedriekVos\LaravelComposerAudit';

arch('every class is final and strictly typed')
    ->expect($domain)
    ->toBeFinal()
    ->toUseStrictTypes();

/*
 * Each expectation carries its own ignore list: Pest applies `ignoring()` to the
 * last expectation of a chain only, so this one stands on its own.
 */
arch('domain classes are readonly')
    ->expect($domain)
    ->toBeReadonly()
    ->ignoring([
        $domain.'\Console',
        $domain.'\Mail',
        $domain.'\LaravelComposerAuditServiceProvider',
    ]);

arch('the domain does not know about requests, responses or the database')
    ->expect($domain)
    ->not->toUse([
        'Illuminate\Http',
        'Illuminate\Database',
        'Illuminate\Routing',
        'Illuminate\View',
    ]);

arch('the console command is suffixed and extends the framework command')
    ->expect($domain.'\Console\Commands')
    ->toHaveSuffix('Command')
    ->toExtend(Command::class);

arch('the mailable extends Mailable')
    ->expect($domain.'\Mail')
    ->toExtend(Mailable::class);

arch('repositories are suffixed Repository')
    ->expect($domain.'\Repositories')
    ->toHaveSuffix('Repository')
    // A source is the read side's own role — it reads raw records and hands the
    // repository DTOs — so it carries the Source suffix instead.
    ->ignoring($domain.'\Repositories\VulnerabilitySource');

arch('file storage is suffixed FileStorage')
    ->expect($domain.'\Storage')
    ->toHaveSuffix('FileStorage');

arch('storage is only reached through a repository, or by the provider registering its disk')
    ->expect($domain.'\Storage')
    ->toOnlyBeUsedIn([
        $domain.'\Repositories',
        $domain.'\LaravelComposerAuditServiceProvider',
    ]);
