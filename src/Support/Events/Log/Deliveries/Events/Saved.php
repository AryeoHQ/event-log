<?php

declare(strict_types=1);

namespace Support\Events\Log\Deliveries\Events;

use Support\Events\Log\Deliveries\Delivery;

final class Saved
{
    public readonly Delivery $delivery;

    public function __construct(Delivery $delivery)
    {
        $this->delivery = $delivery;
    }
}
