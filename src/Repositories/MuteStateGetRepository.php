<?php

declare(strict_types=1);

namespace CedriekVos\LaravelComposerAudit\Repositories;

use CedriekVos\LaravelComposerAudit\Json\MuteStateParser;
use CedriekVos\LaravelComposerAudit\Storage\MuteFileStorage;

final readonly class MuteStateGetRepository
{
    public function __construct(
        private MuteFileStorage $muteFileStorage,
        private MuteStateParser $muteStateParser,
    ) {}

    /**
     * @return array<string, string>
     */
    public function get(): array
    {
        return $this->muteStateParser->parse($this->muteFileStorage->read());
    }
}
