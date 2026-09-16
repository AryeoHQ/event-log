<?php

declare(strict_types=1);

namespace Support\Events\Log\Logs\Listeners;

use Support\Events\Log\Logs\Events\Created;

final class InitiateLifecycle
{
    public function handle(Created $event): void
    {
        rescue(fn () => $event->log->status->lock()->now());
    }
}
