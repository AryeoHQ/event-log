<?php

declare(strict_types=1);

namespace Support\Events\Log\Deliveries\Watchdog;

use Illuminate\Contracts\Queue\ShouldBeUnique;
use Support\Actions\Concerns\AsAction;
use Support\Actions\Contracts\Action;
use Support\Events\Log\Deliveries\Delivery;

final class Bite implements Action, ShouldBeUnique
{
    use AsAction;

    public int $uniqueFor = 3600;

    public function __construct()
    {
        $this->queue = config('event_log.queues.delivery');
    }

    public function handle(): void
    {
        Delivery::using()::query()
            ->stuck()
            ->eachById(
                fn (Delivery $delivery) => rescue(fn () => $delivery->status->fail()->now())
            );
    }
}
