<?php

declare(strict_types=1);

namespace Support\Events\Log\Logs\Status\Triggers;

use Support\Database\Eloquent\StateMachines\Triggers\Phases\Phase;
use Support\Database\Eloquent\StateMachines\Triggers\Phases\TransitionDuring;
use Support\Database\Eloquent\StateMachines\Triggers\Target\Target;
use Support\Database\Eloquent\StateMachines\Triggers\Trigger;
use Support\Events\Log\Logs\Log;

#[TransitionDuring(Phase::Before)]
final class Retry extends Trigger
{
    #[Target]
    protected readonly Log $log;

    public $queue {
        get => $this->log->queue;
    }

    public function handle(): void
    {
        $this->log->status->lock()->now();
    }
}
