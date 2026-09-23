<?php

declare(strict_types=1);

namespace Support\Events\Database\Eloquent\Swappable\Models\Concerns;

use Illuminate\Database\Eloquent\Attributes\CollectedBy;
use Support\Events\Database\Eloquent\Swappable\Swapper\Facades\Swapper;

trait SealsCollection
{
    /**
     * @param  array<array-key, \Illuminate\Database\Eloquent\Model>  $models
     * @return \Illuminate\Database\Eloquent\Collection<array-key, static>
     */
    final public function newCollection(array $models = [])
    {
        Swapper::validateAttribute(static::class, CollectedBy::class);

        return parent::newCollection($models); // @phpstan-ignore return.type
    }
}
