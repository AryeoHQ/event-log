<?php

declare(strict_types=1);

namespace Support\Events\Log\Relays\Status\Events;

use Support\Events\Log\Relays\Relay;

class Processing
{
    public readonly Relay $relay;

    public function __construct(Relay $relay)
    {
        $this->relay = $relay;
    }
}
