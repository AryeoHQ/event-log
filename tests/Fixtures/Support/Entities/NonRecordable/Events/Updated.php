<?php

declare(strict_types=1);

namespace Tests\Fixtures\Support\Entities\NonRecordable\Events;

use Tests\Fixtures\Support\Entities\NonRecordable\NonRecordable;

final class Updated
{
    public NonRecordable $nonRecordable;

    public function __construct(NonRecordable $nonRecordable)
    {
        $this->nonRecordable = $nonRecordable;
    }
}
