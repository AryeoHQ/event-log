<?php

declare(strict_types=1);

namespace Support\Events\Log\Transports\Dispatches\Sending\Contracts;

use Stringable;
use Support\Events\Log\Deliveries\Delivery;

interface NeedsSent
{
    public Delivery $delivery { get; }

    public string $idempotencyKey { get; }

    public null|string|Stringable $result { get; }

    public function result(string|Stringable $result): static;
}
