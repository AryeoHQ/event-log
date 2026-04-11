<?php

declare(strict_types=1);

namespace Support\Events\Log\Deliveries\Status\Events;

use Support\Events\Log\Deliveries\Delivery;

class Failed
{
    public readonly Delivery $model;

    public function __construct(Delivery $model)
    {
        $this->model = $model;
    }
}
