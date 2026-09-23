<?php

declare(strict_types=1);

namespace Tests\Fixtures\Tooling\EventLog;

use Illuminate\Database\Eloquent\Attributes\CollectedBy;
use Illuminate\Database\Eloquent\Attributes\UseEloquentBuilder;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Builder as ForeignBuilder;
use Illuminate\Database\Eloquent\Collection as ForeignCollection;
use Illuminate\Database\Eloquent\Factories\Factory as ForeignFactory;
use Tests\Fixtures\Support\Swappable\Swappable;

#[CollectedBy(ForeignCollection::class)]
#[UseEloquentBuilder(ForeignBuilder::class)]
#[UseFactory(ForeignFactory::class)]
final class SwappableSubclassWithForeignAttributes extends Swappable {}
