<?php

declare(strict_types=1);

namespace Support\Events\Log\Relays\Listeners;

use Support\Events\Log\Relays\Events\Created;

final class InitiateLifecycle
{
    public function handle(Created $event): void
    {
        rescue(fn () => $event->relay->status->lock()->now());
    }
}
