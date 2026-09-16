<?php

declare(strict_types=1);

namespace Support\Events\Log\Logs\Status\Triggers;

use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use Support\Database\Eloquent\StateMachines\Triggers\Target\Target;
use Support\Database\Eloquent\StateMachines\Triggers\Trigger;
use Support\Events\Log\Logs\Integrity\Corrupted;
use Support\Events\Log\Logs\Integrity\Tampered;
use Support\Events\Log\Logs\Log;
use Support\Events\Log\Transports\Contracts\Transport;
use Throwable;

final class Process extends Trigger
{
    use SerializesModels;

    #[Target]
    protected readonly Log $log;

    /** @var int */
    public $tries = 3;

    /** @var list<int> */
    public $backoff = [5, 25];

    public $queue {
        get => $this->log->queue;
    }

    /** @return array<int, object> */
    public function middleware(): array
    {
        return [(new WithoutOverlapping($this->to::class.':'.$this->log->getKey()))->dontRelease()->expireAfter(config('event_log.locking.ttl'))];
    }

    public function handle(): void
    {
        throw_if($this->log->event instanceof Throwable, $this->log->event);

        if (! $this->log->event instanceof Transport) {
            return;
        }

        $this->log->event->transports->each(
            fn (string $relay) => $this->log->relays()->firstOrCreate([
                'transport' => $relay,
            ])
        );
    }

    public function failed(Throwable $throwable): void
    {
        match (true) {
            $throwable instanceof Corrupted, $throwable instanceof Tampered => $this->log->status->compromise()->dispatchAfterFailed()->now(),
            default => $this->log->status->fail()->dispatchAfterFailed()->now(),
        };
    }
}
