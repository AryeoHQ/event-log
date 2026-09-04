<?php

declare(strict_types=1);

namespace Tests\Fixtures\Tooling\EventLog;

use Support\Events\Log;
use Tests\Fixtures\Support\Entities\Recordable\Recordable;
use Tests\Fixtures\Support\Mqtt\Mqtt;

#[Log\Alias\Alias('test.transport')]
final class TransportWithoutHasRelays implements Mqtt
{
    use Log\Provides\HasLoggable;

    #[Log\IdentifiesLoggable\IdentifiesLoggable]
    public Recordable $recordable;

    public function __construct(Recordable $recordable)
    {
        $this->recordable = $recordable;
    }
}
