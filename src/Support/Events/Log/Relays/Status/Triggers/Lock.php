<?php

declare(strict_types=1);

namespace Support\Events\Log\Relays\Status\Triggers;

use Support\Database\Eloquent\StateMachines\Triggers\Phases\Phase;
use Support\Database\Eloquent\StateMachines\Triggers\Phases\TransitionDuring;
use Support\Database\Eloquent\StateMachines\Triggers\Target\Target;
use Support\Database\Eloquent\StateMachines\Triggers\Trigger;
use Support\Events\Log\Relays\Relay;

#[TransitionDuring(Phase::Before)]
final class Lock extends Trigger
{
    #[Target]
    protected readonly Relay $relay;

    public $queue {
        get => $this->relay->queue;
    }

    public function handle(): void
    {
        $this->relay->status->process()->dispatch()->afterCommit();
    }

    public function failed(): void
    {
        $this->relay->status->fail()->dispatchAfterFailed()->now();
    }
}
