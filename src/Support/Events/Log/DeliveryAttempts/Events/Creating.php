<?php

declare(strict_types=1);

namespace Support\Events\Log\DeliveryAttempts\Events;

use Support\Events\Log\DeliveryAttempts\DeliveryAttempt;

final class Creating
{
    public readonly DeliveryAttempt $deliveryAttempt;

    public function __construct(DeliveryAttempt $deliveryAttempt)
    {
        $this->deliveryAttempt = $deliveryAttempt;
    }
}
