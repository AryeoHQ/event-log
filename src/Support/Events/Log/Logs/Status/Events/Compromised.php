<?php

declare(strict_types=1);

namespace Support\Events\Log\Logs\Status\Events;

use Support\Events\Log\Logs\Log;

class Compromised
{
    public readonly Log $log;

    public function __construct(Log $log)
    {
        $this->log = $log;
    }
}
