<?php

declare(strict_types=1);

namespace Support\Events\Database\Eloquent\Swappable\Models\Concerns;

use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Support\Events\Database\Eloquent\Swappable\Swapper\Facades\Swapper;

/**
 * @template TFactory of \Illuminate\Database\Eloquent\Factories\Factory
 */
trait SealsFactory
{
    /** @use \Illuminate\Database\Eloquent\Factories\HasFactory<TFactory> */
    use HasFactory {
        newFactory as protected swappableNewFactory;
    }

    /**
     * @return TFactory|null
     */
    final protected static function newFactory()
    {
        Swapper::validateAttribute(static::class, UseFactory::class);

        return static::swappableNewFactory();
    }
}
