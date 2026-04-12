<?php

declare(strict_types=1);

namespace Support\Events\Log\Attempts\Events;

use Support\Events\Log\Attempts\Attempt;

final class ForceDeleted
{

    public readonly Attempt $entity;

    public function __construct(Attempt $entity)
    {
        $this->entity = $entity;
    }
}
