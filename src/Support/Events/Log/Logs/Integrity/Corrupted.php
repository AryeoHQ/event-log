<?php

declare(strict_types=1);

namespace Support\Events\Log\Logs\Integrity;

use RuntimeException;

final class Corrupted extends RuntimeException
{
    public readonly string $raw;

    public function __construct(string $raw)
    {
        parent::__construct('Corrupted event payload cannot be processed.');

        $this->raw = $raw;
    }
}
