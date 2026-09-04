<?php

declare(strict_types=1);

namespace Support\Events\Log\Contracts;

use Support\Events\Log\Logs\Data\Data;

interface Loggable
{
    public function toLoggable(): Data;
}
