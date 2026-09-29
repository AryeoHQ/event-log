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
    final public function newQuery()
    {
        if (static::using() !== static::class) {
            return $this->newInstance()->newQuery(); // @phpstan-ignore return.type
        }

        return parent::newQuery(); // @phpstan-ignore return.type
    }

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
