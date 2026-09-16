<?php

declare(strict_types=1);

namespace Tests\Fixtures\Tooling\EventLog;

use Support\Events\Log\Relays\Relay;
use Support\Events\Log\Transports\Dispatches\Collecting\Provides\CollectsEnvelopes;

final class CollectsEnvelopesWithoutNeedsEnvelopes
{
    use CollectsEnvelopes;

    public readonly Relay $relay;

    public function __construct(Relay $relay)
    {
        $this->relay = $relay;
    }
}
