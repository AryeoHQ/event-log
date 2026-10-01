<?php

declare(strict_types=1);

namespace Support\Events\Log\Relays\Watchdog;

use Illuminate\Contracts\Queue\ShouldBeUnique;
use Support\Actions\Concerns\AsAction;
use Support\Actions\Contracts\Action;
use Support\Events\Log\Relays\Relay;

final class Bite implements Action, ShouldBeUnique
{
    use AsAction;

    public int $uniqueFor = 3600;

    public function __construct()
    {
        $this->queue = config('event_log.queues.relay');
    }

    public function handle(): void
    {
        Relay::using()::query()
            ->stuck()
            ->eachById(
                fn (Relay $relay) => rescue(fn () => $relay->status->fail()->now())
            );
    }
}
