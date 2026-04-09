<?php

declare(strict_types=1);

namespace Support\Events\Log;

use Support\Events\Log\Actions\RecordEvent;

class Dispatcher implements \Illuminate\Contracts\Events\Dispatcher
{
    private readonly \Illuminate\Contracts\Events\Dispatcher $decorated;

    public function __construct(\Illuminate\Contracts\Events\Dispatcher $dispatcher)
    {
        $this->decorated = $dispatcher;
    }

    public function dispatch($event, $payload = [], $halt = false)
    {
        return with(
            RecordEvent::make($event)->now(),
            fn () => $this->decorated->dispatch($event, $payload, $halt)
        );
    }

    public function listen($events, $listener = null): void
    {
        $this->decorated->listen($events, $listener);
    }

    public function hasListeners($eventName): bool
    {
        return $this->decorated->hasListeners($eventName);
    }

    public function subscribe($subscriber): void
    {
        $this->decorated->subscribe($subscriber);
    }

    public function until($event, $payload = [])
    {
        return $this->decorated->until($event, $payload);
    }

    public function push($event, $payload = []): void
    {
        $this->decorated->push($event, $payload);
    }

    public function flush($event): void
    {
        $this->decorated->flush($event);
    }

    public function forget($event): void
    {
        $this->decorated->forget($event);
    }

    public function forgetPushed(): void
    {
        $this->decorated->forgetPushed();
    }

    /**
     * @param  array<array-key, mixed>  $parameters
     */
    public function __call(string $method, array $parameters): mixed
    {
        return $this->decorated->$method(...$parameters);
    }
}
