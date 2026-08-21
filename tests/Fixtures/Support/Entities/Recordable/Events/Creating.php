<?php

declare(strict_types=1);

namespace Tests\Fixtures\Support\Entities\Recordable\Events;

use Tests\Fixtures\Support\Entities\Recordable\Recordable;

final class Creating
{
    public Recordable $recordable;

    public function __construct(Recordable $recordable)
    {
        $this->recordable = $recordable;
    }
}
