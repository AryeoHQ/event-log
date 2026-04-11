<?php

declare(strict_types=1);

namespace Support\Events\Log\Destinations\Status\Triggers;

use Support\Database\Eloquent\StateMachines\Triggers\Phases\Phase;
use Support\Database\Eloquent\StateMachines\Triggers\Phases\TransitionDuring;
use Support\Database\Eloquent\StateMachines\Triggers\Target\Target;
use Support\Database\Eloquent\StateMachines\Triggers\Trigger;
use Support\Events\Log\Destinations\Destination;

#[TransitionDuring(Phase::Before)]
final class Prepare extends Trigger
{
    #[Target]
    public readonly Destination $destination;

    public function handle(): void
    {
        $this->destination->status->process()->now();
    }

    public function failed(): void
    {
        $this->destination->status->fail()->now();
    }
}
