<?php

declare(strict_types=1);

namespace Tests\Fixtures\Tooling\EventLog;

use Support\Events\Log;
use Tests\Fixtures\Support\Entities\Recordable\Recordable;

final class HasLoggableWithMultipleIdentifiesLoggableAttributes implements Log\Contracts\Recordable
{
    use Log\Provides\HasLoggable;

    #[Log\IdentifiesLoggable\IdentifiesLoggable]
    public readonly Recordable $first;

    #[Log\IdentifiesLoggable\IdentifiesLoggable]
    public readonly Recordable $second;

    public function __construct() {}
}
