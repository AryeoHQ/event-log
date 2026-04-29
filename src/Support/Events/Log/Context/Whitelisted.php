<?php

declare(strict_types=1);

namespace Support\Events\Log\Context;

use Illuminate\Contracts\Database\Eloquent\Castable;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Log\Context\Repository;
use Illuminate\Support\Facades\Context;
use InvalidArgumentException;
use JsonSerializable;

/**
 * @implements \Illuminate\Contracts\Support\Arrayable<string, mixed>
 */
final class Whitelisted implements Arrayable, Castable, JsonSerializable
{
    public readonly Repository $context;

    public function __construct(null|Repository $context = null)
    {
        $this->context = $context ?? Context::getFacadeRoot();
    }

    /**
     * @param  array<string>  $arguments
     * @return \Illuminate\Contracts\Database\Eloquent\CastsAttributes<self, self|\Illuminate\Log\Context\Repository|null>
     */
    public static function castUsing(array $arguments): CastsAttributes
    {
        return new class implements CastsAttributes
        {
            /**
             * @param  array<string, mixed>  $attributes
             */
            public function get(Model $model, string $key, mixed $value, array $attributes): null|Whitelisted
            {
                if ($value === null) {
                    return null;
                }

                /** @var array<string, mixed> $decoded */
                $decoded = json_decode((string) $value, associative: true, flags: JSON_THROW_ON_ERROR);

                return new Whitelisted(app()->build(Repository::class)->add($decoded));
            }

            /**
             * @param  array<string, mixed>  $attributes
             * @return array<string, string>
             */
            public function set(Model $model, string $key, mixed $value, array $attributes): array
            {
                $whitelisted = match (true) {
                    $value === null => new Whitelisted,
                    $value instanceof Whitelisted => $value,
                    $value instanceof Repository => new Whitelisted($value),
                    default => throw new InvalidArgumentException(
                        Whitelisted::class.' cast expects '.Whitelisted::class.', '.Repository::class.', or null; got '.get_debug_type($value)
                    ),
                };

                return [$key => json_encode($whitelisted->toArray(), JSON_THROW_ON_ERROR)];
            }
        };
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        /** @var list<string> $whitelist */
        $whitelist = config('event_log.context.whitelist', []);

        return collect($whitelist)
            ->mapWithKeys(fn (string $key): array => [$key => $this->context->get($key)])
            ->all();
    }

    public function get(string $key): mixed
    {
        return data_get($this->toArray(), $key);
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
