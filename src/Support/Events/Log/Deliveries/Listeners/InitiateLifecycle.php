<?php

declare(strict_types=1);

namespace Support\Events\Log\Deliveries\Listeners;

use Support\Events\Log\Deliveries\Events\Created;

final class InitiateLifecycle
{
    public function handle(Created $event): void
    {
        rescue(fn () => $event->delivery->status->lock()->now());
    }
}
