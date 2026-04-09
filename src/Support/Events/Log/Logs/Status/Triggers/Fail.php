<?php

declare(strict_types=1);

namespace Support\Events\Log\Logs\Status\Triggers;

use Support\Database\Eloquent\StateMachines\Triggers\Target\Target;
use Support\Database\Eloquent\StateMachines\Triggers\Trigger;
use Support\Events\Log\Logs\Entities\Log;

final class Fail extends Trigger
{
    #[Target]
    public readonly Log $log;

    public function handle(): void
    {
        //
    }
}
