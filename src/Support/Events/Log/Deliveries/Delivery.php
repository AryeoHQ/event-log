<?php

declare(strict_types=1);

namespace Support\Events\Log\Deliveries;

use Illuminate\Database\Eloquent\Attributes\CollectedBy;
use Illuminate\Database\Eloquent\Attributes\UseEloquentBuilder;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Support\Events\Log\Deliveries\Builder\Builder;
use Support\Events\Log\Deliveries\Collection\Deliveries;
use Support\Events\Log\Deliveries\Events;
use Support\Events\Log\Deliveries\Factory\Factory;
use Support\Events\Log\Deliveries\Policy\Policy;
use Support\Events\Log\Deliveries\Status\Status;

/**
 * @use HasFactory<Factory>
 *
 * @property (\Support\Events\Log\Deliveries\Status\Status & \Support\Database\Eloquent\StateMachines\StateMachine) $status
 *
 * @phpstan-property \Support\Database\Eloquent\StateMachines\StateMachine<\Support\Events\Log\Deliveries\Status\Status> $status
 */
#[CollectedBy(Deliveries::class)]
#[UseEloquentBuilder(Builder::class)]
#[UseFactory(Factory::class)]
#[UsePolicy(Policy::class)]
class Delivery extends Model
{
    use HasFactory;
    use HasUuids;

    /**
     * @var array<string, class-string>
     */
    protected $dispatchesEvents = [
        'retrieved' => Events\Retrieved::class,
        'creating' => Events\Creating::class,
        'created' => Events\Created::class,
        'updating' => Events\Updating::class,
        'updated' => Events\Updated::class,
        'saving' => Events\Saving::class,
        'saved' => Events\Saved::class,
        'restoring' => Events\Restoring::class,
        'restored' => Events\Restored::class,
        'replicating' => Events\Replicating::class,
        'trashed' => Events\Trashed::class,
        'deleting' => Events\Deleting::class,
        'deleted' => Events\Deleted::class,
        'forceDeleting' => Events\ForceDeleting::class,
        'forceDeleted' => Events\ForceDeleted::class,
    ];

    protected $table = 'event_log_deliveries';

    protected $fillable = [
        'event_log_destination_id',
        'payload',
        'delivery_processor',
        'status',
    ];

    protected $casts = [
        'payload' => 'array',
        'status' => Status::class,
    ];

    protected $attributes = [
        'status' => 'ready',
    ];
}
