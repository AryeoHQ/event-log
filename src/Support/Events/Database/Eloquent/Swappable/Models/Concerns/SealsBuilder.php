<?php

declare(strict_types=1);

namespace Support\Events\Database\Eloquent\Swappable\Models\Concerns;

use Illuminate\Database\Eloquent\Attributes\UseEloquentBuilder;
use Support\Events\Database\Eloquent\Swappable\Swapper\Facades\Swapper;

/**
 * @template TBuilder
 */
trait SealsBuilder
{
    /**
     * @param  \Illuminate\Database\Query\Builder  $query
     * @return TBuilder
     */
    final public function newEloquentBuilder($query)
    {
        Swapper::validateAttribute(static::class, UseEloquentBuilder::class);

        return parent::newEloquentBuilder($query); // @phpstan-ignore return.type
    }
}
