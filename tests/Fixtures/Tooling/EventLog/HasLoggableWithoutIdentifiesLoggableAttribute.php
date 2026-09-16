<?php

declare(strict_types=1);

namespace Tests\Fixtures\Tooling\EventLog;

use Support\Events\Log;
use Tests\Fixtures\Support\Entities\Recordable\Recordable;

final class HasLoggableWithoutIdentifiesLoggableAttribute implements Log\Contracts\Recordable
{
    use Log\Provides\HasLoggable;

    public readonly Recordable $recordable;

    public function __construct() {}
}
