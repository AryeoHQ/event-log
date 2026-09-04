<?php

declare(strict_types=1);

namespace Support\Events\Log\DeliveryAttempts\Status\Events;

use Support\Events\Log\DeliveryAttempts\DeliveryAttempt;

class Failing
{
    public readonly DeliveryAttempt $deliveryAttempt;

    public function __construct(DeliveryAttempt $deliveryAttempt)
    {
        $this->deliveryAttempt = $deliveryAttempt;
    }
}
