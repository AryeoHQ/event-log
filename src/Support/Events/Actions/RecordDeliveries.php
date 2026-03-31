<?php

declare(strict_types=1);

namespace Support\Events\Actions;

use Support\Actions\Concerns\AsAction;
use Support\Actions\Contracts\Action;
use Support\Events\Destinations\Entities\Destination;

final class RecordDeliveries implements Action
{
    use AsAction;

    public readonly Destination $eventLogDestination;

    public function __construct(Destination $eventLogDestination)
    {
        $this->eventLogDestination = $eventLogDestination;
    }

    public function handle(): void
    {
        // Lookup Delivery processor from Provider to create delivery records
        // For each dispatch a ProcessDelivery job
    }
}
