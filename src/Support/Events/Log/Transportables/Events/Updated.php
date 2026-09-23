<?php

declare(strict_types=1);

namespace Support\Events\Log\Transportables\Events;

use Support\Events\Log\Transportables\Transportable;

class Updated
{
    final public readonly Transportable $transportable;

    public function __construct(Transportable $transportable)
    {
        $this->transportable = $transportable;
    }
}
