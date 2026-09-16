<?php

declare(strict_types=1);

namespace Tests\Fixtures\Tooling\EventLog;

use Stringable;
use Support\Events\Log\Deliveries\Delivery;
use Support\Events\Log\Transports\Dispatches\Sending\Contracts\NeedsSent;
use Support\Events\Log\Transports\Dispatches\Sending\Results\Result;

final class NeedsSentWithoutRecordsResult implements NeedsSent
{
    public readonly Delivery $delivery;

    public private(set) null|Result $result = null;

    public function __construct(Delivery $delivery)
    {
        $this->delivery = $delivery;
    }

    public function record(Result|string|Stringable $result): static
    {
        $this->result = $result instanceof Result ? $result : Result::make($result);

        return $this;
    }
}
