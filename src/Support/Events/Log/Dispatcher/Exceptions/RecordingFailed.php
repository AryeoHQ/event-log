<?php

declare(strict_types=1);

namespace Support\Events\Log\Dispatcher\Exceptions;

use Illuminate\Log\LogManager;
use RuntimeException;
use Throwable;

final class RecordingFailed extends RuntimeException
{
    public static function from(Throwable $exception): self
    {
        return new self('Failed to record the event.', previous: $exception);
    }

    public function report(LogManager $log): void
    {
        // Write through the underlying logger so reporting can't fire MessageLogged
        // and loop back through the event recorder that raised this failure.
        $log->channel()->getLogger()->error($this->getMessage(), [ // @phpstan-ignore method.notFound
            'exception' => $this,
        ]);
    }
}
