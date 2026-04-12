<?php

declare(strict_types=1);

namespace Support\Events\Log\Logs\Events;

use Support\Events\Log\Logs\Log;

final class Restoring
{

    public readonly Log $entity;

    public function __construct(Log $entity)
    {
        $this->entity = $entity;
    }
}
