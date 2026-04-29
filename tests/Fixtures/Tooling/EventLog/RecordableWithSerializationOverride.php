<?php

declare(strict_types=1);

namespace Tests\Fixtures\Tooling\EventLog;

use Support\Events\Log\Alias\Alias;
use Support\Events\Log\Contracts\Recordable;
use Support\Events\Log\IdentifiesLoggable\IdentifiesLoggable;
use Support\Events\Log\Provides\HasLoggable;
use Tests\Fixtures\Support\Entities\Articles\Article;

#[Alias('test.serialization_override')]
final class RecordableWithSerializationOverride implements Recordable
{
    use HasLoggable;

    public function __construct(
        #[IdentifiesLoggable]
        public readonly Article $article,
    ) {}

    public function __serialize(): array
    {
        return [];
    }

    public function __unserialize(array $data): void {}
}
