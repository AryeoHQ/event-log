<?php

declare(strict_types=1);

namespace Support\Events\Log\Attempts\Status\Triggers;

use Support\Database\Eloquent\StateMachines\Triggers\Target\Target;
use Support\Database\Eloquent\StateMachines\Triggers\Trigger;
use Support\Events\Log\Attempts\Entities\Attempt;

final class Fail extends Trigger
{
    #[Target]
    public readonly Attempt $attempt;

    public function handle(): void
    {
        //
    }
}
