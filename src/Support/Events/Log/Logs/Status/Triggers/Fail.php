<?php

declare(strict_types=1);

namespace Support\Events\Log\Logs\Status\Triggers;

use Support\Database\Eloquent\StateMachines\Triggers\Target\Target;
use Support\Database\Eloquent\StateMachines\Triggers\Trigger;
use Support\Events\Log\Logs\Log;

final class Fail extends Trigger
{
    #[Target]
    protected readonly Log $log;

    public $queue {
        get => $this->log->queue;
    }

    /** @var int */
    public $tries = 3;

    /** @var list<int> */
    public $backoff = [5, 25];

    public function handle(): void {}
}
