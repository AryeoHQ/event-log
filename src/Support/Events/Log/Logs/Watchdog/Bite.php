<?php

declare(strict_types=1);

namespace Support\Events\Log\Logs\Watchdog;

use Support\Actions\Concerns\AsAction;
use Support\Actions\Contracts\Action;
use Support\Events\Log\Logs\Log;

final class Bite implements Action
{
    use AsAction;

    public function __construct()
    {
        $this->queue = config('event_log.queues.'.Log::class);
    }

    public function handle(): void
    {
        Log::query()
            ->stuck()
            ->eachById(
                fn (Log $log) => rescue(fn () => $log->status->fail()->now())
            );
    }
}
