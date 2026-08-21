<?php

declare(strict_types=1);

namespace Support\Events\Log\Logs\Events;

use Support\Events\Log\Logs\Log;

final class Created
{
    public readonly Log $log;

    public function __construct(Log $log)
    {
        $this->log = $log;
    }
}
