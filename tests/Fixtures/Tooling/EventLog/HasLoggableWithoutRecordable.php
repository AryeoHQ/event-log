<?php

declare(strict_types=1);

namespace Tests\Fixtures\Tooling\EventLog;

use Support\Events\Log\Provides\HasLoggable;

final class HasLoggableWithoutRecordable
{
    use HasLoggable;
}
