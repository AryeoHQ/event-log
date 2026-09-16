<?php

declare(strict_types=1);

namespace Support\Events\Log\Deliveries\Status\Triggers;

use Support\Database\Eloquent\StateMachines\Triggers\Target\Target;
use Support\Database\Eloquent\StateMachines\Triggers\Trigger;
use Support\Events\Log\Deliveries\Delivery;

final class Succeed extends Trigger
{
    #[Target]
    protected readonly Delivery $delivery;

    public $queue {
        get => $this->delivery->queue;
    }

    /** @var int */
    public $tries = 3;

    /** @var list<int> */
    public $backoff = [5, 25];

    public function handle(): void {}
}
