<?php

declare(strict_types=1);

namespace Support\Events\Log\Attempts\Events;

use Support\Entities\Events\Attributes\Alias;
use Support\Entities\Events\Contracts\ForEntity;
use Support\Entities\Events\Provides\EntityDriven;
use Support\Events\Log\Attempts\Entities\Attempt;

#[Alias('event-log-delivery-attempt.force-deleted')]
final class ForceDeleted implements ForEntity
{
    use EntityDriven;

    public readonly Attempt $entity;

    public function __construct(Attempt $entity)
    {
        $this->entity = $entity;
    }
}
