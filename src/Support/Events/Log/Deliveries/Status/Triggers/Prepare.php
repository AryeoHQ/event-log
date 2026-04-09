<?php

declare(strict_types=1);

namespace Support\Events\Log\Deliveries\Status\Triggers;

use Support\Database\Eloquent\StateMachines\Triggers\Phases\Phase;
use Support\Database\Eloquent\StateMachines\Triggers\Phases\TransitionDuring;
use Support\Database\Eloquent\StateMachines\Triggers\Target\Target;
use Support\Database\Eloquent\StateMachines\Triggers\Trigger;
use Support\Events\Log\Deliveries\Entities\Delivery;

#[TransitionDuring(Phase::Before)]
final class Prepare extends Trigger
{
    #[Target]
    public readonly Delivery $delivery;

    public function handle(): void
    {
        $this->delivery->status->process()->now();
    }
}
