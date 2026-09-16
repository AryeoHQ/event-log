<?php

declare(strict_types=1);

namespace Support\Events\Log\Transports\Contracts;

use Illuminate\Support\Collection;
use Support\Events\Log\Contracts\Recordable;

interface Transport extends Recordable
{
    /**
     * @var \Illuminate\Support\Collection<array-key, class-string<\Support\Events\Log\Transports\Contracts\Transport>>
     **/
    public Collection $transports { get; }
}
