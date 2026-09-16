<?php

declare(strict_types=1);

namespace Support\Events\Log\Relays\Watchdog;

use Support\Actions\Concerns\AsAction;
use Support\Actions\Contracts\Action;
use Support\Events\Log\Relays\Relay;

final class Bite implements Action
{
    use AsAction;

    public function __construct()
    {
        $this->queue = config('event_log.queues.'.Relay::class);
    }

    public function handle(): void
    {
        Relay::query()
            ->stuck()
            ->eachById(
                fn (Relay $relay) => rescue(fn () => $relay->status->fail()->now())
            );
    }
}
