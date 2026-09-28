<?php

declare(strict_types=1);

namespace Tests\Fixtures\Support\Swappable;

use Illuminate\Database\Eloquent\Attributes\CollectedBy;
use Illuminate\Database\Eloquent\Attributes\UseEloquentBuilder;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Support\Events\Log\Relays\Builder;
use Support\Events\Log\Relays\Collection\Relays;
use Support\Events\Log\Relays\Factory;
use Support\Events\Log\Relays\Relay;

/**
 * @property (\Support\Events\Log\Relays\Status\Status & \Support\Database\Eloquent\StateMachines\StateMachine) $status
 *
 * @phpstan-property \Support\Database\Eloquent\StateMachines\StateMachine<\Support\Events\Log\Relays\Status\Status> $status
 */
#[CollectedBy(Relays::class)]
#[UseEloquentBuilder(Builder::class)]
#[UseFactory(Factory::class)]
class SwappedRelay extends Relay {}
