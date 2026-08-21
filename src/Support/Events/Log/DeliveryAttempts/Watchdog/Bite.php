<?php

declare(strict_types=1);

namespace Support\Events\Log\DeliveryAttempts\Watchdog;

use Support\Actions\Concerns\AsAction;
use Support\Actions\Contracts\Action;
use Support\Events\Log\DeliveryAttempts\DeliveryAttempt;

final class Bite implements Action
{
    use AsAction;

    public function __construct()
    {
        $this->queue = config('event_log.queues.'.DeliveryAttempt::class);
    }

    public function handle(): void
    {
        DeliveryAttempt::query()
            ->stuck()
            ->eachById(
                fn (DeliveryAttempt $attempt) => rescue(fn () => $attempt->status->fail()->now())
            );
    }
}
