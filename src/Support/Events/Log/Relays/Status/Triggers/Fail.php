<?php

declare(strict_types=1);

namespace Support\Events\Log\Relays\Status\Triggers;

use Support\Database\Eloquent\StateMachines\Triggers\Target\Target;
use Support\Database\Eloquent\StateMachines\Triggers\Trigger;
use Support\Events\Log\Relays\Relay;

final class Fail extends Trigger
{
    #[Target]
    protected readonly Relay $relay;

    public $queue {
        get => $this->relay->queue;
    }

    /** @var int */
    public $tries = 3;

    /** @var list<int> */
    public $backoff = [5, 25];

    public function handle(): void {}
}
