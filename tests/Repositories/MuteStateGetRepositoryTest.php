<?php

use CedriekVos\LaravelComposerAudit\Json\MuteStateParser;
use CedriekVos\LaravelComposerAudit\Repositories\MuteStateGetRepository;
use CedriekVos\LaravelComposerAudit\Storage\MuteFileStorage;
use Illuminate\Support\Facades\Storage;

covers(MuteStateGetRepository::class);

beforeEach(function () {
    Storage::fake('laravel-composer-audit');
    $this->repository = new MuteStateGetRepository(new MuteFileStorage, new MuteStateParser);
});

it('reads and decodes the stored mute map', function () {
    Storage::disk('laravel-composer-audit')->put('vulnerability-mutes.json', '{"GHSA-aaaa|vendor/foo":"2026-06-09T12:00:00+00:00"}');

    expect($this->repository->get())->toBe(['GHSA-aaaa|vendor/foo' => '2026-06-09T12:00:00+00:00']);
});

it('returns an empty map when nothing is stored', function () {
    expect($this->repository->get())->toBe([]);
});
