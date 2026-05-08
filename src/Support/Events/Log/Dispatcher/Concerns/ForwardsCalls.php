<?php

declare(strict_types=1);

namespace Support\Events\Log\Dispatcher\Concerns;

/**
 * @mixin \Support\Events\Log\Dispatcher\Dispatcher
 */
trait ForwardsCalls
{
    /**
     * @param  \Closure|string|array<mixed>  $events
     * @param  \Closure|string|array<mixed>|null  $listener
     */
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

    /**
     * @param  string  $event
     * @param  array<mixed>  $payload
     */
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
