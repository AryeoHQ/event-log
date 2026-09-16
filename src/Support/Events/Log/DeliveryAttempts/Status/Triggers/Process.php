<?php

declare(strict_types=1);

namespace Support\Events\Log\DeliveryAttempts\Status\Triggers;

use Support\Database\Eloquent\StateMachines\Triggers\Target\Target;
use Support\Database\Eloquent\StateMachines\Triggers\Trigger;
use Support\Database\Eloquent\StateMachines\Triggers\WithoutTransaction;
use Support\Events\Log\DeliveryAttempts\DeliveryAttempt;
use Support\Events\Log\DeliveryAttempts\Exceptions\Undeliverable;
use Support\Events\Log\Transports\Dispatches\Dispatches;
use Support\Events\Log\Transports\Dispatches\Sending\Results\Result;
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

        // A result may have been captured on the event before the listener throws to signal
        // failure. The finally block writes it before the exception propagates. failed() only
        // falls back to the exception message when no result was recorded.
        try {
            event($event);
        } finally {
            when(
                $event->result !== null,
                fn () => $this->deliveryAttempt->update(['result' => $event->result])
            );
        }
    }

    public function failed(Throwable $throwable): void
    {
        when(
            $this->deliveryAttempt->fresh()->result === null,
            fn () => $this->deliveryAttempt->update(['result' => Result::make($throwable->getMessage())])
        );

        match (true) {
            $throwable instanceof Undeliverable => $this->deliveryAttempt->status->disqualify()->dispatchAfterFailed()->now(),
            default => $this->deliveryAttempt->status->fail()->dispatchAfterFailed()->now(),
        };
    }
}
