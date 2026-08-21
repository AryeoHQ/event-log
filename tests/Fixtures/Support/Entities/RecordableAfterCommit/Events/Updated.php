<?php

declare(strict_types=1);

namespace Tests\Fixtures\Support\Entities\RecordableAfterCommit\Events;

use Support\Events\Log;
use Support\Events\Log\Alias\Alias;
use Tests\Fixtures\Support\Entities\RecordableAfterCommit\RecordableAfterCommit;

#[Alias('recordable_after_commit.updated')]
final class Updated implements Log\Contracts\RecordableAfterCommit
{
    use Log\Provides\HasLoggable;

    #[Log\IdentifiesLoggable\IdentifiesLoggable]
    public RecordableAfterCommit $recordableAfterCommit;

    public function __construct(RecordableAfterCommit $recordableAfterCommit)
    {
        $this->recordableAfterCommit = $recordableAfterCommit;
    }
}
