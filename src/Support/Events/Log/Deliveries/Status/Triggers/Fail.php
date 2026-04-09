<?php

declare(strict_types=1);

namespace Support\Events\Log\Deliveries\Status\Triggers;

use Support\Database\Eloquent\StateMachines\Triggers\Target\Target;
use Support\Database\Eloquent\StateMachines\Triggers\Trigger;
use Support\Events\Log\Deliveries\Entities\Delivery;

final class Fail extends Trigger
{
    #[Target]
    public readonly Delivery $delivery;

    public function handle(): void
    {
        //
    }
}
