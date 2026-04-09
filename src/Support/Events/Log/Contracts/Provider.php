<?php

declare(strict_types=1);

namespace Support\Events\Log\Contracts;

interface Provider
{
    /** @var class-string<DestinationProcessor> */
    public string $destinationProcessor { get; }

    /** @var class-string<DeliveryProcessor> */
    public string $deliveryProcessor { get; }
}
