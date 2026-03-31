<?php

declare(strict_types=1);

namespace Support\Events\Jobs;

use Support\Actions\Concerns\AsAction;
use Support\Actions\Contracts\Action;
use Support\Events\Deliveries\Entities\Delivery;

final class ProcessDelivery implements Action
{
    use AsAction;

    public readonly Delivery $delivery;

    public function __construct(Delivery $delivery)
    {
        $this->delivery = $delivery;
    }

    public function handle(): void
    {
        // Process the delivery
    }
}
