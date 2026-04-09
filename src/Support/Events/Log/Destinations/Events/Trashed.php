<?php

declare(strict_types=1);

namespace Support\Events\Log\Destinations\Events;

use Support\Entities\Events\Attributes\Alias;
use Support\Entities\Events\Contracts\ForEntity;
use Support\Entities\Events\Provides\EntityDriven;
use Support\Events\Log\Destinations\Entities\Destination;

#[Alias('event-log-destination.trashed')]
final class Trashed implements ForEntity
{
    use EntityDriven;

    public readonly Destination $entity;

    public function __construct(Destination $entity)
    {
        $this->entity = $entity;
    }
}
