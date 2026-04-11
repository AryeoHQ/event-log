<?php

declare(strict_types=1);

namespace Support\Events\Log\Attempts\Status\Triggers;

use Support\Database\Eloquent\StateMachines\Triggers\Target\Target;
use Support\Database\Eloquent\StateMachines\Triggers\Trigger;
use Support\Events\Log\Attempts\Attempt;

final class Process extends Trigger
{
    #[Target]
    public readonly Attempt $attempt;

    public function handle(): void
    {
        //
    }
}
