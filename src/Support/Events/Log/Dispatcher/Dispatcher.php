<?php

declare(strict_types=1);

namespace Support\Events\Log\Dispatcher;

use Support\Events\Log\Actions\LogEvent;
use Support\Events\Log\Dispatcher\Exceptions\RecordingFailed;
use Throwable;

final class Dispatcher implements \Illuminate\Contracts\Events\Dispatcher
{
    use Concerns\ForwardsCalls;

    private readonly \Illuminate\Contracts\Events\Dispatcher $decorated;

    public function __construct(\Illuminate\Contracts\Events\Dispatcher $dispatcher)
    {
        $this->decorated = $dispatcher;
    }

    /**
     * @param  string|object  $event
     * @param  mixed  $payload
     * @param  bool  $halt
     * @return array<array-key, mixed>|null
     */
    public function dispatch($event, $payload = [], $halt = false)
    {
        $this->record($event);

        return $this->decorated->dispatch($event, $payload, $halt);
    }

    public function until($event, $payload = [])
    {
        $this->record($event);

        return $this->decorated->until($event, $payload);
    }

    private function record(string|object $event): void
    {
        rescue(
            fn () => LogEvent::make($event)->dispatchAfterFailed()->now(),
            report: fn (Throwable $exception) => report(RecordingFailed::from($exception)),
        );
    }
}
