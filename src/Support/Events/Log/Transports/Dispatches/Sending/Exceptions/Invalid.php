<?php

declare(strict_types=1);

namespace Support\Events\Log\Transports\Dispatches\Sending\Exceptions;

use RuntimeException;

final class Invalid extends RuntimeException
{
    public function __construct(string $class)
    {
        parent::__construct("Sending event [{$class}] must implement NeedsSent.");
    }
}
