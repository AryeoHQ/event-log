<?php

declare(strict_types=1);

namespace Support\Events\Log\DeliveryAttempts\Status;

use Support\Database\Eloquent\StateMachines\Attributes\Events\Events;
use Support\Database\Eloquent\StateMachines\Attributes\Transitions\Transition;
use Support\Database\Eloquent\StateMachines\Contracts\StateMachineable;
use Support\Database\Eloquent\StateMachines\Provides\ManagesState;
use Support\Events\Log\DeliveryAttempts\Status\Events\Failed;
use Support\Events\Log\DeliveryAttempts\Status\Events\Failing;
use Support\Events\Log\DeliveryAttempts\Status\Events\Locked;
use Support\Events\Log\DeliveryAttempts\Status\Events\Locking;
use Support\Events\Log\DeliveryAttempts\Status\Events\Pended;
use Support\Events\Log\DeliveryAttempts\Status\Events\Pending;
use Support\Events\Log\DeliveryAttempts\Status\Events\Succeeded;
use Support\Events\Log\DeliveryAttempts\Status\Events\Succeeding;
use Support\Events\Log\DeliveryAttempts\Status\Events\Undeliverabled;
use Support\Events\Log\DeliveryAttempts\Status\Events\Undeliverabling;
use Support\Events\Log\DeliveryAttempts\Status\Triggers\Disqualify;
use Support\Events\Log\DeliveryAttempts\Status\Triggers\Fail;
use Support\Events\Log\DeliveryAttempts\Status\Triggers\Lock;
use Support\Events\Log\DeliveryAttempts\Status\Triggers\Process;

/**
 * @method \Support\Events\Log\DeliveryAttempts\Status\Triggers\Lock lock()
 * @method \Support\Events\Log\DeliveryAttempts\Status\Triggers\Fail fail()
 * @method \Support\Events\Log\DeliveryAttempts\Status\Triggers\Disqualify disqualify()
 * @method \Support\Events\Log\DeliveryAttempts\Status\Triggers\Process process()
 */
enum Status: string implements StateMachineable
{
    use ManagesState;

    #[Events(before: Pending::class, after: Pended::class)]
    #[Transition(to: self::Locked, using: Lock::class)]
    #[Transition(to: self::Failed, using: Fail::class)]
    #[Transition(to: self::Undeliverable, using: Disqualify::class)]
    case Pending = 'pending';

    #[Events(before: Locking::class, after: Locked::class)]
    #[Transition(to: self::Succeeded, using: Process::class)]
    #[Transition(to: self::Failed, using: Fail::class)]
    #[Transition(to: self::Undeliverable, using: Disqualify::class)]
    case Locked = 'locked';

    #[Events(before: Succeeding::class, after: Succeeded::class)]
    case Succeeded = 'succeeded';

    #[Events(before: Failing::class, after: Failed::class)]
    case Failed = 'failed';

    #[Events(before: Undeliverabling::class, after: Undeliverabled::class)]
    case Undeliverable = 'undeliverable';
}
