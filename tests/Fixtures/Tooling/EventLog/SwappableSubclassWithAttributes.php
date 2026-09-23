<?php

declare(strict_types=1);

namespace Tests\Fixtures\Tooling\EventLog;

use Illuminate\Database\Eloquent\Attributes\CollectedBy;
use Illuminate\Database\Eloquent\Attributes\UseEloquentBuilder;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Tests\Fixtures\Support\Swappable\Builder;
use Tests\Fixtures\Support\Swappable\Collection\Swappables;
use Tests\Fixtures\Support\Swappable\Factory;
use Tests\Fixtures\Support\Swappable\Swappable;

#[CollectedBy(Swappables::class)]
#[UseEloquentBuilder(Builder::class)]
#[UseFactory(Factory::class)]
final class SwappableSubclassWithAttributes extends Swappable {}
