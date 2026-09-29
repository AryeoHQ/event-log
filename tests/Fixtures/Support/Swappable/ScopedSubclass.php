<?php

declare(strict_types=1);

namespace Tests\Fixtures\Support\Swappable;

use Illuminate\Database\Eloquent\Attributes\CollectedBy;
use Illuminate\Database\Eloquent\Attributes\UseEloquentBuilder;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Tests\Fixtures\Support\Swappable\Collection\Swappables;

#[CollectedBy(Swappables::class)]
#[UseEloquentBuilder(Builder::class)]
#[UseFactory(Factory::class)]
final class ScopedSubclass extends Swappable
{
    protected static function booted(): void
    {
        self::addGlobalScope('visible', fn (EloquentBuilder $query) => $query->where('name', 'visible'));
    }
}
