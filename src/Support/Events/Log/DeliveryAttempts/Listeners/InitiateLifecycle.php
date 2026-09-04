<?php

declare(strict_types=1);

namespace Support\Events\Log\DeliveryAttempts\Listeners;

use Support\Events\Log\DeliveryAttempts\Events\Created;

final class InitiateLifecycle
{
    public function handle(Created $event): void
    {
        $event->deliveryAttempt->status->lock()->now();
    }
}
