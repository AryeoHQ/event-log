<?php

declare(strict_types=1);

namespace Support\Events\Log\Relays\Status;

use Support\Database\Eloquent\StateMachines\Attributes\Events\Events;
use Support\Database\Eloquent\StateMachines\Attributes\Transitions\Transition;
use Support\Database\Eloquent\StateMachines\Contracts\StateMachineable;
use Support\Database\Eloquent\StateMachines\Provides\ManagesState;
use Support\Events\Log\Relays\Status\Events\Failed;
use Support\Events\Log\Relays\Status\Events\Failing;
use Support\Events\Log\Relays\Status\Events\Locked;
use Support\Events\Log\Relays\Status\Events\Locking;
use Support\Events\Log\Relays\Status\Events\Pended;
use Support\Events\Log\Relays\Status\Events\Pending;
use Support\Events\Log\Relays\Status\Events\Processed;
use Support\Events\Log\Relays\Status\Events\Processing;
use Support\Events\Log\Relays\Status\Triggers\Fail;
use Support\Events\Log\Relays\Status\Triggers\Lock;
use Support\Events\Log\Relays\Status\Triggers\Process;
use Support\Events\Log\Relays\Status\Triggers\Retry;

/**
 * @method \Support\Events\Log\Relays\Status\Triggers\Lock lock()
 * @method \Support\Events\Log\Relays\Status\Triggers\Fail fail()
 * @method \Support\Events\Log\Relays\Status\Triggers\Process process()
 * @method \Support\Events\Log\Relays\Status\Triggers\Retry retry()
 */
enum Status: string implements StateMachineable
{
    use ManagesState;

    #[Events(before: Pending::class, after: Pended::class)]
    #[Transition(to: self::Locked, using: Lock::class)]
    #[Transition(to: self::Failed, using: Fail::class)]
    case Pending = 'pending';

    #[Events(before: Locking::class, after: Locked::class)]
    #[Transition(to: self::Processed, using: Process::class)]
    #[Transition(to: self::Failed, using: Fail::class)]
    case Locked = 'locked';

    #[Events(before: Processing::class, after: Processed::class)]
    case Processed = 'processed';

    #[Events(before: Failing::class, after: Failed::class)]
    #[Transition(to: self::Pending, using: Retry::class)]
    case Failed = 'failed';
}
