<?php

declare(strict_types=1);

namespace Support\Events\Log\Deliveries\Events;

use Support\Events\Log\Deliveries\Delivery;

final class Restored
{

    public readonly Delivery $entity;

    public function __construct(Delivery $entity)
    {
        $this->entity = $entity;
    }
}
