<?php

declare(strict_types=1);

namespace Support\Events\Log\Logs\Events;

use Support\Entities\Events\Attributes\Alias;
use Support\Entities\Events\Contracts\ForEntity;
use Support\Entities\Events\Provides\EntityDriven;
use Support\Events\Log\Logs\Entities\Log;

final class Created
{
    public readonly Log $log;

    public function __construct(Log $log)
    {
        $this->log = $log;
    }
}
