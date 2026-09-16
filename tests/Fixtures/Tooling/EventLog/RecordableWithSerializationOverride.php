<?php

declare(strict_types=1);

namespace Tests\Fixtures\Tooling\EventLog;

use Support\Events\Log;
use Support\Events\Log\Alias\Alias;
use Tests\Fixtures\Support\Entities\Recordable\Recordable;

#[Alias('test.serialization_override')]
final class RecordableWithSerializationOverride implements Log\Contracts\Recordable
{
    use Log\Provides\HasLoggable;

    #[Log\IdentifiesLoggable\IdentifiesLoggable]
    public Recordable $recordable;

    public function __construct(Recordable $recordable)
    {
        $this->recordable = $recordable;
    }

    public function __serialize(): array
    {
        return [];
    }

    public function __unserialize(array $data): void {}
}
