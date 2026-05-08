<?php

declare(strict_types=1);

namespace Support\Events\Log\Exceptions;

use RuntimeException;

class IntegrityViolation extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('The event log could not be verified.');
    }
}
