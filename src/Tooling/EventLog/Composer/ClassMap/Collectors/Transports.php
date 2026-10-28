<?php

declare(strict_types=1);

namespace Tooling\EventLog\Composer\ClassMap\Collectors;

use ReflectionClass;
use Support\Events\Log\Transports\Contracts\Transport;
use Tooling\Composer\ClassMap\Collectors\Contracts\Collector;
use Tooling\Composer\ClassMap\Collectors\Provides\Fakeable;

final class Transports implements Collector
{
    use Fakeable;

    /**
     * @param  array<class-string, \ReflectionClass<*>>  $reflections
     */
    public function collects(ReflectionClass $class, array $reflections): bool
    {
        return ! str_contains($class->getName(), 'Fixtures\\Tooling')
            && $class->implementsInterface(Transport::class)
            && ! $class->isAbstract();
    }
}
