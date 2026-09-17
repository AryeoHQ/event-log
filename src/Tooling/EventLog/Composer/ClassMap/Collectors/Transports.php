<?php

declare(strict_types=1);

namespace Tooling\EventLog\Composer\ClassMap\Collectors;

use Illuminate\Support\Collection;
use ReflectionClass;
use Support\Events\Log\Transports\Contracts\Transport;
use Tooling\Composer\ClassMap\Collectors\Contracts\Collector;
use Tooling\Composer\ClassMap\Collectors\Provides\Fakeable;

final class Transports implements Collector
{
    use Fakeable;

    /**
     * @param  \Illuminate\Support\Collection<int, class-string>  $classes
     * @return \Illuminate\Support\Collection<int, class-string>
     */
    public function collect(Collection $classes): Collection
    {
        return $classes
            ->reject(fn (string $class): bool => str_contains($class, 'Fixtures\\Tooling'))
            ->filter(fn (string $class): bool => rescue(
                fn (): bool => with(
                    new ReflectionClass($class),
                    fn (ReflectionClass $reflection): bool => is_a($class, Transport::class, true)
                        && ! $reflection->isInterface()
                        && ! $reflection->isAbstract(),
                ),
                false,
                false,
            ))
            ->values();
    }
}
