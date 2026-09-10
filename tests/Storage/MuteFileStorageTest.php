<?php

use CedriekVos\LaravelComposerAudit\Storage\MuteFileStorage;
use Illuminate\Support\Facades\Storage;

covers(MuteFileStorage::class);

beforeEach(function () {
    Storage::fake('laravel-composer-audit');
    $this->storage = new MuteFileStorage;
});

it('returns an empty string when nothing has been written', function () {
    expect($this->storage->read())->toBe('');
});

it('writes contents and reads them back', function () {
    $this->storage->write('{"a|b":"2026"}');

    expect($this->storage->read())->toBe('{"a|b":"2026"}');
});

it('stores the file on its own dedicated disk', function () {
    $this->storage->write('{}');

    expect(Storage::disk('laravel-composer-audit')->exists('vulnerability-mutes.json'))->toBeTrue();
});
