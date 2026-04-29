<?php

declare(strict_types=1);

namespace Support\Events\Log\Dispatcher;

use Support\Events\Log\Actions\LogEvent;

class Dispatcher implements \Illuminate\Contracts\Events\Dispatcher
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
        rescue(fn () => LogEvent::make($event)->now(), report: true);

        return $this->decorated->dispatch($event, $payload, $halt);
    }
}
