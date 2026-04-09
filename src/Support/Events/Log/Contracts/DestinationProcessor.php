<?php

declare(strict_types=1);

namespace Support\Events\Log\Contracts;

use Illuminate\Support\Collection;
use Support\Events\Log\Destinations\Entities\Destination;

interface DestinationProcessor
{
    /**
     * @return \Illuminate\Support\Collection<int, array{payload: array<mixed>, delivery_processor: class-string<DeliveryProcessor>}>
     */
    public function deliveries(Destination $destination): Collection;
}
