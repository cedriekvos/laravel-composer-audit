<?php

use CedriekVos\LaravelComposerAudit\Json\MuteStateSerializer;
use CedriekVos\LaravelComposerAudit\Repositories\MuteStateWriteRepository;
use CedriekVos\LaravelComposerAudit\Storage\MuteFileStorage;
use Illuminate\Support\Facades\Storage;

covers(MuteStateWriteRepository::class);

beforeEach(function () {
    Storage::fake('laravel-composer-audit');
    $this->repository = new MuteStateWriteRepository(new MuteFileStorage, new MuteStateSerializer);
});

it('serializes and writes the mute map to storage', function () {
    $this->repository->save(['GHSA-aaaa|vendor/foo' => '2026-06-09T12:00:00+00:00']);

    expect(Storage::disk('laravel-composer-audit')->get('vulnerability-mutes.json'))
        ->toBe('{"GHSA-aaaa|vendor\/foo":"2026-06-09T12:00:00+00:00"}');
});
