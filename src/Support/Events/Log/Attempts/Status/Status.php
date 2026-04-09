<?php

declare(strict_types=1);

namespace Support\Events\Log\Attempts\Status;

use Support\Database\Eloquent\StateMachines\Attributes\Events\Events;
use Support\Database\Eloquent\StateMachines\Attributes\Transitions\Transition;
use Support\Database\Eloquent\StateMachines\Contracts\StateMachineable;
use Support\Database\Eloquent\StateMachines\Provides\ManagesState;
use Support\Events\Log\Attempts\Status\Events\Failed;
use Support\Events\Log\Attempts\Status\Events\Failing;
use Support\Events\Log\Attempts\Status\Events\Prepared;
use Support\Events\Log\Attempts\Status\Events\Preparing;
use Support\Events\Log\Attempts\Status\Events\Processed;
use Support\Events\Log\Attempts\Status\Events\Processing;
use Support\Events\Log\Attempts\Status\Triggers\Fail;
use Support\Events\Log\Attempts\Status\Triggers\Prepare;
use Support\Events\Log\Attempts\Status\Triggers\Process;

/**
 * @method \Support\Events\Log\Attempts\Status\Triggers\Prepare prepare()
 * @method \Support\Events\Log\Attempts\Status\Triggers\Fail fail()
 * @method \Support\Events\Log\Attempts\Status\Triggers\Process process()
 */
enum Status: string implements StateMachineable
{
    use ManagesState;

    #[Events(before: Preparing::class, after: Prepared::class)]
    #[Transition(to: self::Pending, using: Prepare::class)]
    #[Transition(to: self::Failed, using: Fail::class)]
    case Ready = 'ready';

    #[Events(before: Processing::class, after: Processed::class)]
    #[Transition(to: self::Processed, using: Process::class)]
    #[Transition(to: self::Failed, using: Fail::class)]
    case Pending = 'pending';

    #[Events(before: Processing::class, after: Processed::class)]
    case Processed = 'processed';

    #[Events(before: Failing::class, after: Failed::class)]
    case Failed = 'failed';
}
