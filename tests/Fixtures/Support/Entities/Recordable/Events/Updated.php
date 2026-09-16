<?php

declare(strict_types=1);

namespace Tests\Fixtures\Support\Entities\Recordable\Events;

use Support\Events\Log;
use Support\Events\Log\Alias\Alias;
use Tests\Fixtures\Support\Entities\Recordable\Recordable;

#[Alias('recordable.updated')]
final class Updated implements Log\Contracts\Recordable
{
    use Log\Provides\HasLoggable;

    #[Log\IdentifiesLoggable\IdentifiesLoggable]
    public Recordable $recordable;

    public function __construct(Recordable $recordable)
    {
        $this->recordable = $recordable;
    }
}
