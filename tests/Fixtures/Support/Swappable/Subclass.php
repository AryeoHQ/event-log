<?php

declare(strict_types=1);

namespace Tests\Fixtures\Support\Swappable;

use Illuminate\Database\Eloquent\Attributes\CollectedBy;
use Illuminate\Database\Eloquent\Attributes\UseEloquentBuilder;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Tests\Fixtures\Support\Swappable\Collection\Swappables;

#[CollectedBy(Swappables::class)]
#[UseEloquentBuilder(Builder::class)]
#[UseFactory(Factory::class)]
final class Subclass extends Swappable
{
    protected $casts = ['name' => 'boolean', 'extra' => 'boolean'];

    protected $dispatchesEvents = ['created' => Events\SubclassCreated::class];
}
