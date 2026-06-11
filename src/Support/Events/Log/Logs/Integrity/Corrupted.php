<?php

declare(strict_types=1);

namespace Support\Events\Log\Logs\Integrity;

final readonly class Corrupted
{
    public string $raw;

    public function __construct(string $raw)
    {
        $this->raw = $raw;
    }
}
