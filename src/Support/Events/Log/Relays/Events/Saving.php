<?php

declare(strict_types=1);

namespace Support\Events\Log\Relays\Events;

use Support\Events\Log\Relays\Relay;

final class Saving
{
    public readonly Relay $relay;

    public function __construct(Relay $relay)
    {
        $this->relay = $relay;
    }
}
