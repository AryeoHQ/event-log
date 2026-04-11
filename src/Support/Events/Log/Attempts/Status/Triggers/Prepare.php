<?php

declare(strict_types=1);

namespace Support\Events\Log\Attempts\Status\Triggers;

use Support\Database\Eloquent\StateMachines\Triggers\Phases\Phase;
use Support\Database\Eloquent\StateMachines\Triggers\Phases\TransitionDuring;
use Support\Database\Eloquent\StateMachines\Triggers\Target\Target;
use Support\Database\Eloquent\StateMachines\Triggers\Trigger;
use Support\Events\Log\Attempts\Attempt;

#[TransitionDuring(Phase::Before)]
final class Prepare extends Trigger
{
    #[Target]
    public readonly Attempt $attempt;

    public function handle(): void
    {
        $this->attempt->status->process()->now();
    }
}
