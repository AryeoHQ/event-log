<?php

declare(strict_types=1);

namespace Tests\Support\Events\Log\Contracts;

use Support\Events\Log\Contracts\Recordable;

interface TestsRecordable
{
    public Recordable $event { get; }
}
