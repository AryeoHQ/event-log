<?php

declare(strict_types=1);

namespace Support\Events\Log\Transports\Dispatches\Collecting\Exceptions;

use RuntimeException;
use Support\Events\Log\Transports\Dispatches\Collecting\Contracts\NeedsEnvelopes;

final class Invalid extends RuntimeException
{
    public function __construct(string $class)
    {
        parent::__construct('Collecting event ['.class_basename($class).'] must implement '.class_basename(NeedsEnvelopes::class).'.');
    }
}
