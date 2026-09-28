<?php

declare(strict_types=1);

namespace Tests\Fixtures\Support\Swappable;

use Illuminate\Database\Eloquent\Attributes\CollectedBy;
use Illuminate\Database\Eloquent\Attributes\UseEloquentBuilder;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Support\Events\Log\Deliveries\Builder;
use Support\Events\Log\Deliveries\Collection\Deliveries;
use Support\Events\Log\Deliveries\Delivery;
use Support\Events\Log\Deliveries\Factory;

/**
 * @property (\Support\Events\Log\Deliveries\Status\Status & \Support\Database\Eloquent\StateMachines\StateMachine) $status
 *
 * @phpstan-property \Support\Database\Eloquent\StateMachines\StateMachine<\Support\Events\Log\Deliveries\Status\Status> $status
 */
#[CollectedBy(Deliveries::class)]
#[UseEloquentBuilder(Builder::class)]
#[UseFactory(Factory::class)]
class SwappedDelivery extends Delivery {}
