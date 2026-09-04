<?php

declare(strict_types=1);

namespace Support\Events\Log\Transports\Dispatches\Sending\Provides;

use Stringable;

/**
 * @phpstan-require-implements \Support\Events\Log\Transports\Dispatches\Sending\Contracts\NeedsSent
 */
trait RecordsResult
{
    public private(set) null|string|Stringable $result = null;

    public string $idempotencyKey {
        get => $this->delivery->id;
    }

    public function result(string|Stringable $result): static
    {
        $this->result = $result;

        return $this;
    }
}
