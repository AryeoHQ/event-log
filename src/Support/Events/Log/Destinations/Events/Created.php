<?php

declare(strict_types=1);

namespace Support\Events\Log\Destinations\Events;

use Support\Events\Log\Destinations\Destination;

final class Created
{

    public readonly Destination $entity;

    public function __construct(Destination $entity)
    {
        $this->entity = $entity;
    }
}
