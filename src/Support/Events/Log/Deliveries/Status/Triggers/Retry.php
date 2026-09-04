<?php

declare(strict_types=1);

namespace Support\Events\Log\Deliveries\Status\Triggers;

use Support\Database\Eloquent\StateMachines\Triggers\Phases\Phase;
use Support\Database\Eloquent\StateMachines\Triggers\Phases\TransitionDuring;
use Support\Database\Eloquent\StateMachines\Triggers\Target\Target;
use Support\Database\Eloquent\StateMachines\Triggers\Trigger;
use Support\Events\Log\Deliveries\Delivery;

#[TransitionDuring(Phase::Before)]
final class Retry extends Trigger
{
    #[Target]
    protected readonly Delivery $delivery;

    public $queue {
        get => $this->delivery->queue;
    }

    public function handle(): void
    {
        $this->delivery->increment('tries');

        $this->delivery->status->lock()->now();
    }
}
