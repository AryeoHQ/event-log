<?php

declare(strict_types=1);

namespace Tests\Fixtures\Tooling\EventLog;

use Support\Events\Log\Contracts\Recordable;
use Support\Events\Log\IdentifiesLoggable\IdentifiesLoggable;
use Support\Events\Log\Provides\HasLoggable;

final class HasLoggableWithInvalidIdentifiesLoggableType implements Recordable
{
    use HasLoggable;

    #[IdentifiesLoggable]
    public readonly string $name;

    public function __construct() {}
}
