<?php

declare(strict_types=1);

namespace Support\Events\Database\Eloquent\Swappable\Swapper\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static void replace(string $swappable, string $with)
 * @method static string using(string $swappable)
 * @method static void validateAttribute(string $model, string $attribute)
 * @method static string declared(string $model, string $attribute)
 * @method static bool isSwappable(string $model)
 * @method static string origin(string $model)
 * @method static void consolidate(\Illuminate\Database\Eloquent\Model $model)
 * @method static void validateEvents(\Illuminate\Database\Eloquent\Model $model)
 * @method static array<array-key, mixed> inherited(string $model, string $property)
 *
 * @see \Support\Events\Database\Eloquent\Swappable\Swapper\Swapper
 */
final class Swapper extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Support\Events\Database\Eloquent\Swappable\Swapper\Swapper::class;
    }
}
