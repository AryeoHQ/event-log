<?php

declare(strict_types=1);

namespace Support\Events\Log\Destinations\Status\Triggers;

use Support\Database\Eloquent\StateMachines\Triggers\Target\Target;
use Support\Database\Eloquent\StateMachines\Triggers\Trigger;
use Support\Events\Log\Destinations\Destination;

final class Fail extends Trigger
{
    #[Target]
    public readonly Destination $destination;

    public function handle(): void
    {
        //
    }
}
