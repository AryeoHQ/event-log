<?php

declare(strict_types=1);

namespace Support\Events\Log\Deliveries\Events;

use Support\Entities\Events\Attributes\Alias;
use Support\Entities\Events\Contracts\ForEntity;
use Support\Entities\Events\Provides\EntityDriven;
use Support\Events\Log\Deliveries\Entities\Delivery;

#[Alias('event-log-delivery.updating')]
final class Updating implements ForEntity
{
    use EntityDriven;

    public readonly Delivery $entity;

    public function __construct(Delivery $entity)
    {
        $this->entity = $entity;
    }
}
