<?php

declare(strict_types=1);

namespace Tests\Fixtures\Support\Entities\Transportables;

use Illuminate\Database\Eloquent\Attributes\CollectedBy;
use Illuminate\Database\Eloquent\Attributes\UseEloquentBuilder;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Tests\Fixtures\Support\Entities\Transportables\Collection\Transportables;

#[CollectedBy(Transportables::class)]
#[UseEloquentBuilder(Builder::class)]
#[UseFactory(Factory::class)]
final class Transportable extends \Support\Events\Log\Transportables\Transportable {}
