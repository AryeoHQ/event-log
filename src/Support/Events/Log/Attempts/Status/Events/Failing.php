<?php

declare(strict_types=1);

namespace Support\Events\Log\Attempts\Status\Events;

use Support\Events\Log\Attempts\Entities\Attempt;

class Failing
{
    public readonly Attempt $model;

    public function __construct(Attempt $model)
    {
        $this->model = $model;
    }
}
