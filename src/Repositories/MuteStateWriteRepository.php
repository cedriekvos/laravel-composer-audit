<?php

declare(strict_types=1);

namespace CedriekVos\LaravelComposerAudit\Repositories;

use CedriekVos\LaravelComposerAudit\Json\MuteStateSerializer;
use CedriekVos\LaravelComposerAudit\Storage\MuteFileStorage;

final readonly class MuteStateWriteRepository
{
    public function __construct(
        private MuteFileStorage $muteFileStorage,
        private MuteStateSerializer $muteStateSerializer,
    ) {}

    /**
     * @param  array<string, string>  $state
     */
    public function save(array $state): void
    {
        $this->muteFileStorage->write($this->muteStateSerializer->serialize($state));
    }
}
