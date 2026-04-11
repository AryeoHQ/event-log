<?php

declare(strict_types=1);

namespace Support\Events\Log\Destinations\Status\Events;

use Support\Events\Log\Destinations\Destination;

class Processed
{
    public readonly Destination $model;

    public function __construct(Destination $model)
    {
        $this->model = $model;
    }
}
