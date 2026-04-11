<?php

declare(strict_types=1);

namespace Support\Events\Log\Logs\Status\Events;

use Support\Events\Log\Logs\Log;

class Processed
{
    public readonly Log $model;

    public function __construct(Log $model)
    {
        $this->model = $model;
    }
}
