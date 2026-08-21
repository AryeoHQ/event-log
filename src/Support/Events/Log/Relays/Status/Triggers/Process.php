<?php

declare(strict_types=1);

namespace Support\Events\Log\Relays\Status\Triggers;

use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use ReflectionClass;
use Support\Database\Eloquent\StateMachines\Triggers\Target\Target;
use Support\Database\Eloquent\StateMachines\Triggers\Trigger;
use Support\Events\Log\Deliveries\Tries\Tries;
use Support\Events\Log\Envelopes\Envelope;
use Support\Events\Log\Relays\Relay;
use Support\Events\Log\Transports\Dispatches\Dispatches;

final class Process extends Trigger
{
    use SerializesModels;

    #[Target]
    protected readonly Relay $relay;

    /** @var int */
    public $tries = 3;

    /** @var list<int> */
    public $backoff = [5, 25];

    public $queue {
        get => $this->relay->queue;
    }

    /** @return array<int, object> */
    public function middleware(): array
    {
        return [(new WithoutOverlapping($this->to::class.':'.$this->relay->getKey()))->dontRelease()->expireAfter(config('event_log.locking.ttl'))];
    }

    /** @var \ReflectionClass<object> */
    private ReflectionClass $reflection {
        get => $this->reflection ??= new ReflectionClass($this->relay->transport);
    }

    private Dispatches $dispatches {
        get => $this->dispatches ??= Dispatches::on($this->relay->transport);
    }

    private null|int $deliveryTries {
        get => $this->deliveryTries ??= data_get(
            $this->reflection->getAttributes(Tries::class),
            0
        )?->newInstance()->count;
    }

    public function handle(): void
    {
        $event = new ($this->dispatches->collecting)($this->relay);

        event($event);

        $event->envelopes->each(
            fn (Envelope $envelope) => $this->relay->deliveries()->firstOrCreate(
                ['envelope' => $envelope],
                ['tries' => $this->deliveryTries],
            )
        );
    }

    public function failed(): void
    {
        $this->relay->status->fail()->dispatchAfterFailed()->now();
    }
}
