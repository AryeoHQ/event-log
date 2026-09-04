<?php

declare(strict_types=1);

namespace Support\Events\Log\Provides;

use Illuminate\Support\Collection;
use ReflectionClass;
use Support\Events\Log\Transports\Contracts\Transport;

trait HasRelays
{
    /**
     * @var \Illuminate\Support\Collection<array-key, class-string<\Support\Events\Log\Transports\Contracts\Transport>>
     */
    public Collection $transports {
        get => collect((new ReflectionClass($this))->getInterfaceNames())
            ->filter(fn (string $interface): bool => is_subclass_of($interface, Transport::class));
    }
}
