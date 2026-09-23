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
final class SubclassWithForeignEvent extends Swappable
{
    /**
     * @var array<string, class-string>
     */
    protected $dispatchesEvents = [
        'created' => Events\ForeignCreated::class,
    ];
}
