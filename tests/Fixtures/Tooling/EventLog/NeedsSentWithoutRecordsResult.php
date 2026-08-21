<?php

declare(strict_types=1);

namespace Tests\Fixtures\Tooling\EventLog;

use Stringable;
use Support\Events\Log\Deliveries\Delivery;
use Support\Events\Log\Transports\Dispatches\Sending\Contracts\NeedsSent;

final class NeedsSentWithoutRecordsResult implements NeedsSent
{
    public readonly Delivery $delivery;

    public private(set) null|string|Stringable $result = null;

    public function __construct(Delivery $delivery)
    {
        $this->delivery = $delivery;
    }

    public function result(string|Stringable $result): static
    {
        $this->result = $result;

        return $this;
    }
}
