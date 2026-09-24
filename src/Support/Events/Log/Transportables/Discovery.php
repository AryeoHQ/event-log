<?php

declare(strict_types=1);

namespace Support\Events\Log\Transportables;

use Illuminate\Support\Collection;
use ReflectionClass;
use Support\Events\Log\Transports\Contracts\Transport;
use Tooling\Composer\ClassMap\Cache;
use Tooling\EventLog\Composer\ClassMap\Collectors\Transports;

final class Discovery
{
    protected Cache $cache { get => $this->cache ??= resolve(Cache::class); }

    /** @var Collection<int, class-string<Transport>> */
    public Collection $classes {
        get => $this->classes ??= collect($this->cache->get(Transports::class) ?? [])->values();
    }

    /** @var Collection<int, array{id: string, class: class-string<Transport>, transports: array<int, class-string<Transport>>}> */
    public Collection $transportables {
        get => $this->transportables ??= $this->classes
            ->map(fn (string $class): Transport => new ReflectionClass($class)->newInstanceWithoutConstructor())
            ->map(fn (Transport $event): array => [
                'id' => (string) $event->alias,
                'class' => $event::class,
                'transports' => $event->transports->values()->all(),
            ])
            ->values();
    }
}
