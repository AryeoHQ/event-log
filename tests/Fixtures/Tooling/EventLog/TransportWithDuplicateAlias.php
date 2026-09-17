<?php

declare(strict_types=1);

namespace Tests\Fixtures\Tooling\EventLog;

use Support\Events\Log;
use Tests\Fixtures\Support\Entities\Recordable\Recordable;
use Tests\Fixtures\Support\Mqtt\Mqtt;

#[Log\Alias\Alias('test.transport')]
final class TransportWithDuplicateAlias implements Mqtt
{
    use Log\Provides\HasLoggable;
    use Log\Provides\HasRelays;

    #[Log\IdentifiesLoggable\IdentifiesLoggable]
    public Recordable $recordable;

    public function __construct(Recordable $recordable)
    {
        $this->recordable = $recordable;
    }
}
