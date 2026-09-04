<?php

declare(strict_types=1);

namespace Support\Events\Log\DeliveryAttempts\Status\Triggers;

use Support\Database\Eloquent\StateMachines\Triggers\Target\Target;
use Support\Database\Eloquent\StateMachines\Triggers\Trigger;
use Support\Database\Eloquent\StateMachines\Triggers\WithoutTransaction;
use Support\Events\Log\DeliveryAttempts\DeliveryAttempt;
use Support\Events\Log\DeliveryAttempts\Exceptions\Undeliverable;
use Support\Events\Log\Transports\Dispatches\Dispatches;
use Throwable;

#[WithoutTransaction]
final class Process extends Trigger
{
    #[Target]
    protected readonly DeliveryAttempt $deliveryAttempt;

    public $queue {
        get => $this->deliveryAttempt->queue;
    }

    private Dispatches $dispatches {
        get => $this->dispatches ??= Dispatches::on($this->deliveryAttempt->delivery->relay->transport);
    }

    public function handle(): void
    {
        $event = new ($this->dispatches->sending)($this->deliveryAttempt->delivery);

        $this->deliveryAttempt->update(['attempted_at' => now()]);

        event($event);

        when(
            $event->result !== null,
            fn () => $this->deliveryAttempt->update(['response' => (string) $event->result])
        );
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
