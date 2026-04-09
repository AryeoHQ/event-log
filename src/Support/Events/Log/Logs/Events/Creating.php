<?php

declare(strict_types=1);

namespace Support\Events\Log\Logs\Events;

use Support\Entities\Events\Attributes\Alias;
use Support\Entities\Events\Contracts\ForEntity;
use Support\Entities\Events\Provides\EntityDriven;
use Support\Events\Log\Logs\Entities\Log;

#[Alias('event-log.creating')]
final class Creating implements ForEntity
{
    use EntityDriven;

    public readonly Log $entity;

    public function __construct(Log $entity)
    {
        $this->entity = $entity;
    }
}
