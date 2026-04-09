<?php

declare(strict_types=1);

namespace Support\Events\Log\Deliveries\Status\Triggers;

use Support\Database\Eloquent\StateMachines\Triggers\Target\Target;
use Support\Database\Eloquent\StateMachines\Triggers\Trigger;
use Support\Events\Log\Attempts\Entities\Attempt;
use Support\Events\Log\Deliveries\Entities\Delivery;

final class Process extends Trigger
{
    #[Target]
    public readonly Delivery $delivery;

    public function handle(): void
    {
        /** @var \Support\Events\Log\Contracts\DeliveryProcessor $processor */
        $processor = app($this->delivery->delivery_processor);

        $response = $processor->deliver($this->delivery);

        $attempt = Attempt::create([
            'event_log_delivery_id' => $this->delivery->id,
            'response' => $response,
            'status' => 'ready',
        ]);

        $attempt->status->prepare()->now();
    }
}
