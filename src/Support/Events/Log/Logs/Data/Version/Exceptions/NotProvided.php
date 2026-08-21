<?php

declare(strict_types=1);

namespace Support\Events\Log\Logs\Data\Version\Exceptions;

use RuntimeException;
use Support\Events\Log\Logs\Data\Version\Contracts\Version;

final class NotProvided extends RuntimeException
{
    public function __construct(object $payload)
    {
        parent::__construct(
            'A '.class_basename(Version::class).' could not be resolved from '.class_basename($payload::class).'.'
        );
    }
}
