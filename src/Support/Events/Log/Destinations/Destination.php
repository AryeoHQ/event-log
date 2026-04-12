<?php

declare(strict_types=1);

namespace Support\Events\Log\Destinations;

use Illuminate\Database\Eloquent\Attributes\CollectedBy;
use Illuminate\Database\Eloquent\Attributes\UseEloquentBuilder;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Support\Events\Log\Destinations\Builder\Builder;
use Support\Events\Log\Destinations\Collection\Destinations;
use Support\Events\Log\Destinations\Events;
use Support\Events\Log\Destinations\Factory\Factory;
use Support\Events\Log\Destinations\Policy\Policy;
use Support\Events\Log\Destinations\Status\Status;
use Support\Events\Log\Logs\Log;

/**
 * @use HasFactory<Factory>
 *
 * @property (\Support\Events\Log\Destinations\Status\Status & \Support\Database\Eloquent\StateMachines\StateMachine) $status
 *
 * @phpstan-property \Support\Database\Eloquent\StateMachines\StateMachine<\Support\Events\Log\Destinations\Status\Status> $status
 */
#[CollectedBy(Destinations::class)]
#[UseEloquentBuilder(Builder::class)]
#[UseFactory(Factory::class)]
#[UsePolicy(Policy::class)]
class Destination extends Model
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

    protected $table = 'event_log_destinations';

    protected $fillable = [
        'event_log_id',
        'destination_processor',
        'status',
    ];

    protected $casts = [
        'status' => Status::class,
    ];

    protected $attributes = [
        'status' => 'ready',
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<Log, $this>
     */
    public function log(): BelongsTo
    {
        return $this->belongsTo(Log::class, 'event_log_id');
    }
}
