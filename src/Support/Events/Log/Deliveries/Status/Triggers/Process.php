<?php

declare(strict_types=1);

namespace Support\Events\Log\Deliveries\Status\Triggers;

use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use Support\Database\Eloquent\StateMachines\Triggers\Target\Target;
use Support\Database\Eloquent\StateMachines\Triggers\Trigger;
use Support\Database\Eloquent\StateMachines\Triggers\WithoutTransaction;
use Support\Events\Log\Deliveries\Delivery;
use Support\Events\Log\DeliveryAttempts\Exceptions\Undeliverable;
use Throwable;

#[WithoutTransaction]
final class Process extends Trigger
{
    use SerializesModels;

    #[Target]
    protected readonly Delivery $delivery;

    public $queue {
        get => $this->delivery->queue;
    }

    /** @return array<int, object> */
    public function middleware(): array
    {
        return [(new WithoutOverlapping($this->to::class.':'.$this->delivery->getKey()))->dontRelease()->expireAfter(config('event_log.locking.ttl'))];
    }

    public int $tries {
        get => $this->tries ??= $this->delivery->tries - $this->delivery->attempts_count;
    }

    /** @var array<int, int> */
    public array $backoff {
        get => $this->backoff ??= $this->tries > 1
            ? collect(range(1, $this->tries - 1))->map(fn (int $n) => 5 ** $n)->all()
            : [];
    }

    public function handle(): void
    {
        $this->delivery->touch();

        if (! $this->delivery->is_deliverable) {
            $this->delivery->status->disqualify()->dispatchAfterFailed()->now();

            return;
        }

        try {
            $this->delivery->attempts()->create();
        } catch (Undeliverable) {
            $this->delivery->status->disqualify()->dispatchAfterFailed()->now();

            return;
        }

        $this->delivery->status->succeed()->dispatchAfterFailed()->now();
    }

    public function failed(Throwable $throwable): void
    {
        match (true) {
            $throwable instanceof Undeliverable => $this->delivery->status->disqualify()->dispatchAfterFailed()->now(),
            default => $this->delivery->status->fail()->dispatchAfterFailed()->now(),
        };
    }
}
