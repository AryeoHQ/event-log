<?php

declare(strict_types=1);

namespace Support\Events\Log\Transports\Dispatches;

use Attribute;
use ReflectionClass;
use Support\Events\Log\Transports\Dispatches\Exceptions\NotDefined;

#[Attribute(Attribute::TARGET_CLASS)]
final class Dispatches
{
    /**
     * @var class-string<\Support\Events\Log\Transports\Dispatches\Collecting\Contracts\NeedsEnvelopes>
     */
    public readonly string $collecting;

    /**
     * @var class-string<\Support\Events\Log\Transports\Dispatches\Sending\Contracts\NeedsSent>
     */
    public readonly string $sending;

    /**
     * @param  class-string<\Support\Events\Log\Transports\Dispatches\Collecting\Contracts\NeedsEnvelopes>  $collecting
     * @param  class-string<\Support\Events\Log\Transports\Dispatches\Sending\Contracts\NeedsSent>  $sending
     */
    public function __construct(string $collecting, string $sending)
    {
        throw_unless(
            is_a($collecting, Collecting\Contracts\NeedsEnvelopes::class, true),
            new Collecting\Exceptions\Invalid($collecting)
        );
        throw_unless(
            is_a($sending, Sending\Contracts\NeedsSent::class, true),
            new Sending\Exceptions\Invalid($sending)
        );

        $this->collecting = $collecting;
        $this->sending = $sending;
    }

    /**
     * @param  class-string<\Support\Events\Log\Transports\Contracts\Transport>  $transport
     *
     * @throws \Support\Events\Log\Transports\Dispatches\Exceptions\NotDefined
     */
    public static function on(string $transport): self
    {
        $attributes = (new ReflectionClass($transport))->getAttributes(self::class);

        throw_if(count($attributes) === 0, new NotDefined($transport));

        return $attributes[0]->newInstance();
    }
}
