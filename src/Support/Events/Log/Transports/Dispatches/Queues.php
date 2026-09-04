<?php

declare(strict_types=1);

namespace Support\Events\Log\Transports\Dispatches;

use Attribute;
use ReflectionClass;

#[Attribute(Attribute::TARGET_CLASS)]
final class Queues
{
    // These are config keys the transport owns (e.g. 'webhooks.queues.sending'),
    // not queue names — the consumer sets the queue via that config/env.
    public readonly null|string $collecting;

    public readonly null|string $sending;

    public function __construct(null|string $collecting = null, null|string $sending = null)
    {
        $this->collecting = $collecting;
        $this->sending = $sending;
    }

    /**
     * @param  class-string  $transport
     */
    public static function on(string $transport): self
    {
        $attributes = (new ReflectionClass($transport))->getAttributes(self::class);

        return $attributes === [] ? new self : $attributes[0]->newInstance();
    }
}
