<?php

declare(strict_types=1);

namespace Tests\Fixtures\Support\Swappable\Events;

use Tests\Fixtures\Support\Swappable\Swappable;

class Created
{
    final public readonly Swappable $swappable;

    public function __construct(Swappable $swappable)
    {
        $this->swappable = $swappable;
    }
}
