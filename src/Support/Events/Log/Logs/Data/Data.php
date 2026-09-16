<?php

declare(strict_types=1);

namespace Support\Events\Log\Logs\Data;

use Illuminate\Contracts\Database\Eloquent\Castable;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Support\Collection;

final class Data implements Castable
{
    /** @var \Illuminate\Support\Collection<int|string, \Support\Events\Log\Logs\Data\Variant> */
    public readonly Collection $variants;

    private function __construct(Variant ...$variants)
    {
        $this->variants = collect($variants);
    }

    public static function of(Variant ...$variants): self
    {
        return new self(...$variants);
    }

    /**
     * @param  array<string>  $arguments
     * @return \Illuminate\Contracts\Database\Eloquent\CastsAttributes<array<string, mixed>, self>
     */
    public static function castUsing(array $arguments): CastsAttributes
    {
        return new class implements CastsAttributes
        {
            /**
             * @param  \Illuminate\Database\Eloquent\Model  $model
             * @return array<string, mixed>|null
             */
            public function get($model, string $key, mixed $value, array $attributes): null|array
            {
                if ($value === null) {
                    return null;
                }

                return json_decode($value, true);
            }

            /**
             * @param  \Illuminate\Database\Eloquent\Model  $model
             */
            public function set($model, string $key, mixed $value, array $attributes): null|string
            {
                if ($value === null) {
                    return null;
                }

                if (! $value instanceof Data) {
                    throw new \InvalidArgumentException(
                        'data must be a '.class_basename(Data::class).' instance'
                    );
                }

                return json_encode(
                    $value->variants->mapWithKeys(
                        fn (Variant $variant) => [$variant->version->value => $variant->payload]
                    )
                );
            }
        };
    }
}
