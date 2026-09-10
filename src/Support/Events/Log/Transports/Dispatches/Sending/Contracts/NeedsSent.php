<?php

declare(strict_types=1);

namespace Support\Events\Log\Transports\Dispatches\Sending\Contracts;

use Stringable;
use Support\Events\Log\Deliveries\Delivery;
use Support\Events\Log\Transports\Dispatches\Sending\Results\Result;

interface NeedsSent
{
    public Delivery $delivery { get; }

    public string $idempotencyKey { get; }

    public null|Result $result { get; }

    public function record(Result|string|Stringable $result): static;
}
