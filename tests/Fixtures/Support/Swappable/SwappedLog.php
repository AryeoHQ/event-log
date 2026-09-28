<?php

declare(strict_types=1);

namespace Tests\Fixtures\Support\Swappable;

use Illuminate\Database\Eloquent\Attributes\CollectedBy;
use Illuminate\Database\Eloquent\Attributes\UseEloquentBuilder;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Support\Events\Log\Logs\Builder;
use Support\Events\Log\Logs\Collection\Logs;
use Support\Events\Log\Logs\Factory;
use Support\Events\Log\Logs\Log;

/**
 * @property (\Support\Events\Log\Logs\Status\Status & \Support\Database\Eloquent\StateMachines\StateMachine) $status
 *
 * @phpstan-property \Support\Database\Eloquent\StateMachines\StateMachine<\Support\Events\Log\Logs\Status\Status> $status
 */
#[CollectedBy(Logs::class)]
#[UseEloquentBuilder(Builder::class)]
#[UseFactory(Factory::class)]
class SwappedLog extends Log {}
