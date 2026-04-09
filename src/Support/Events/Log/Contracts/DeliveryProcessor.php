<?php

declare(strict_types=1);

namespace Support\Events\Log\Contracts;

use Support\Events\Log\Deliveries\Entities\Delivery;

interface DeliveryProcessor
{
    public function deliver(Delivery $delivery): string;
}
