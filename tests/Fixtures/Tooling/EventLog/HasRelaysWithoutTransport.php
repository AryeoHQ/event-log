<?php

declare(strict_types=1);

namespace Tests\Fixtures\Tooling\EventLog;

use Support\Events\Log;
use Tests\Fixtures\Support\Entities\Recordable\Recordable;

#[Log\Alias\Alias('test.no-transport')]
final class HasRelaysWithoutTransport implements Log\Contracts\Recordable
{
    use Log\Provides\HasLoggable;
    use Log\Provides\HasRelays;

    #[Log\IdentifiesLoggable\IdentifiesLoggable]
    public Recordable $recordable;

    public function __construct(Recordable $recordable)
    {
        $this->recordable = $recordable;
    }
}
