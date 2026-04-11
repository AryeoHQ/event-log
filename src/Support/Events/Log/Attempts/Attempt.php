<?php

declare(strict_types=1);

namespace Support\Events\Log\Attempts;

use Illuminate\Database\Eloquent\Attributes\CollectedBy;
use Illuminate\Database\Eloquent\Attributes\UseEloquentBuilder;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Support\Entities\Contracts\Entity;
use Support\Entities\References\Concerns\AsEntity;
use Support\Events\Log\Attempts\Builder\Builder;
use Support\Events\Log\Attempts\Collection\Attempts;
use Support\Events\Log\Attempts\Events;
use Support\Events\Log\Attempts\Factory\Factory;
use Support\Events\Log\Attempts\Policy\Policy;
use Support\Events\Log\Attempts\Status\Status;

/**
 * @use HasFactory<Factory>
 *
 * @property (\Support\Events\Log\Attempts\Status\Status & \Support\Database\Eloquent\StateMachines\StateMachine) $status
 *
 * @phpstan-property \Support\Database\Eloquent\StateMachines\StateMachine<\Support\Events\Log\Attempts\Status\Status> $status
 */
#[CollectedBy(Attempts::class)]
#[UseEloquentBuilder(Builder::class)]
#[UseFactory(Factory::class)]
#[UsePolicy(Policy::class)]
class Attempt extends Model implements Entity
{
    use AsEntity;
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

    protected $table = 'event_log_delivery_attempts';

    protected $fillable = [
        'event_log_delivery_id',
        'response',
        'status',
    ];

    protected $casts = [
        'status' => Status::class,
    ];

    protected $attributes = [
        'status' => 'ready',
    ];
}
