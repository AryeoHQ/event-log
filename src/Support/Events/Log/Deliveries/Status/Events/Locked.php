<?php

declare(strict_types=1);

namespace Support\Events\Log\Deliveries\Status\Events;

use Support\Events\Log\Deliveries\Delivery;

class Locked
{
    final public readonly Delivery $delivery;

    public function __construct(Delivery $delivery)
    {
        $this->delivery = $delivery;
    }
}
