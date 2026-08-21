<?php

declare(strict_types=1);

namespace Support\Events\Log\Logs\Data;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Jsonable;
use JsonSerializable;
use ReflectionClass;
use ReflectionProperty;
use Support\Events\Log\Logs\Data\Version\Contracts\Version;
use Support\Events\Log\Logs\Data\Version\Exceptions\NotProvided;

final class Variant
{
    /**
     * @var array<string, mixed>
     */
    public readonly array $payload;

    public readonly Version $version;

    /**
     * @param  array<string, mixed>  $payload
     */
    private function __construct(array $payload, Version $version)
    {
        $this->payload = $payload;
        $this->version = $version;
    }

    /**
     * @param  \Illuminate\Contracts\Support\Arrayable<string, mixed>|\JsonSerializable|\Illuminate\Contracts\Support\Jsonable  $payload
     */
    public static function make(Arrayable|JsonSerializable|Jsonable $payload, null|Version $version = null): self
    {
        $resolved = self::discoverVersion($payload) ?? $version;

        throw_unless($resolved, new NotProvided($payload));

        return new self(match (true) {
            $payload instanceof JsonSerializable => $payload->jsonSerialize(),
            $payload instanceof Jsonable => json_decode($payload->toJson(), true),
            $payload instanceof Arrayable => $payload->toArray(),
        }, $resolved);
    }

    private static function discoverVersion(object $payload): null|Version
    {
        foreach ((new ReflectionClass($payload))->getProperties(ReflectionProperty::IS_PUBLIC) as $property) {
            if ($property->isInitialized($payload) && $payload->{$property->getName()} instanceof Version) {
                return $payload->{$property->getName()};
            }
        }

        return null;
    }
}
