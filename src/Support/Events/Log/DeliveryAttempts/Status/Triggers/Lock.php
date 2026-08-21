<?php

declare(strict_types=1);

namespace Support\Events\Log\DeliveryAttempts\Status\Triggers;

use Support\Database\Eloquent\StateMachines\Triggers\Phases\Phase;
use Support\Database\Eloquent\StateMachines\Triggers\Phases\TransitionDuring;
use Support\Database\Eloquent\StateMachines\Triggers\Target\Target;
use Support\Database\Eloquent\StateMachines\Triggers\Trigger;
use Support\Database\Eloquent\StateMachines\Triggers\WithoutTransaction;
use Support\Events\Log\DeliveryAttempts\DeliveryAttempt;
use Support\Events\Log\DeliveryAttempts\Exceptions\Undeliverable;
use Throwable;

#[TransitionDuring(Phase::Before)]
#[WithoutTransaction]
final class Lock extends Trigger
{
    #[Target]
    protected readonly DeliveryAttempt $deliveryAttempt;

    public $queue {
        get => $this->deliveryAttempt->queue;
    }

    public function handle(): void
    {
        $this->deliveryAttempt->status->process()->now();
    }

    public function failed(Throwable $throwable): void
    {
        $this->deliveryAttempt->update(['response' => $throwable->getMessage()]);

        match (true) {
            $throwable instanceof Undeliverable => $this->deliveryAttempt->status->disqualify()->dispatchAfterFailed()->now(),
            default => $this->deliveryAttempt->status->fail()->dispatchAfterFailed()->now(),
        };
    }
}
