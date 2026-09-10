<?php

declare(strict_types=1);

namespace CedriekVos\LaravelComposerAudit\Storage;

use Illuminate\Support\Facades\Storage;

final readonly class MuteFileStorage
{
    /**
     * The disk the mute state lives on. The service provider registers it
     * when the host application has not configured a disk of its own by that name.
     */
    public const string DISK = 'laravel-composer-audit';

    private const string PATH = 'vulnerability-mutes.json';

    public function read(): string
    {
        return Storage::disk(self::DISK)->get(self::PATH) ?? '';
    }

    public function write(string $contents): void
    {
        Storage::disk(self::DISK)->put(self::PATH, $contents);
    }
}
