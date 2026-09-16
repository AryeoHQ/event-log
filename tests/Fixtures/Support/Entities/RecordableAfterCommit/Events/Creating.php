<?php

declare(strict_types=1);

namespace Tests\Fixtures\Support\Entities\RecordableAfterCommit\Events;

use Tests\Fixtures\Support\Entities\RecordableAfterCommit\RecordableAfterCommit;

final class Creating
{
    public RecordableAfterCommit $recordableAfterCommit;

    public function __construct(RecordableAfterCommit $recordableAfterCommit)
    {
        $this->recordableAfterCommit = $recordableAfterCommit;
    }
}
