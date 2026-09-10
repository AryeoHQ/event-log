<?php

declare(strict_types=1);

namespace Support\Events\Log\Transports\Dispatches\Sending\Provides;

use Stringable;
use Support\Events\Log\Transports\Dispatches\Sending\Results\Result;

/**
 * @phpstan-require-implements \Support\Events\Log\Transports\Dispatches\Sending\Contracts\NeedsSent
 */
trait RecordsResult
{
    public private(set) null|Result $result = null;

    public string $idempotencyKey {
        get => $this->delivery->id;
    }

    public function record(Result|string|Stringable $result): static
    {
        $this->result = Result::make($result);

        return $this;
    }
}
