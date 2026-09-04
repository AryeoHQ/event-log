<?php

declare(strict_types=1);

namespace Support\Events\Log\DeliveryAttempts;

use Illuminate\Database\Eloquent\Attributes\CollectedBy;
use Illuminate\Database\Eloquent\Attributes\UseEloquentBuilder;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Support\Events\Log\Deliveries\Delivery;
use Support\Events\Log\DeliveryAttempts\Collection\DeliveryAttempts;
use Support\Events\Log\DeliveryAttempts\Status\Status;

/**
 * @property string $id
 * @property string $delivery_id
 * @property \Support\Events\Log\Deliveries\Delivery $delivery
 * @property \Support\Events\Log\DeliveryAttempts\Status\Status $status
 * @property string|null $response
 * @property \Carbon\CarbonImmutable $attempted_at
 *
 * @phpstan-property \Support\Database\Eloquent\StateMachines\StateMachine<\Support\Events\Log\DeliveryAttempts\Status\Status> $status
 */
#[CollectedBy(DeliveryAttempts::class)]
#[UseEloquentBuilder(Builder::class)]
#[UseFactory(Factory::class)]
class DeliveryAttempt extends Model
{
    /** @use HasFactory<Factory> */
    use HasFactory;

    use HasUuids;

    protected $table = 'event_log_delivery_attempts';

    protected $with = ['delivery'];

    protected $touches = ['delivery']; // @phpstan-ignore missingType.iterableValue (parent declares @var array)

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
        'replicating' => Events\Replicating::class,
        'deleting' => Events\Deleting::class,
        'deleted' => Events\Deleted::class,
    ];

    protected $attributes = [
        'status' => Status::Pending,
    ];

    protected $fillable = [
        'event_log_delivery_id',
        'response',
        'attempted_at',
    ];

    protected $casts = [
        'attempted_at' => 'immutable_datetime',
        'status' => Status::class,
    ];

    public null|string $queue {
        get => $this->delivery->queue;
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\Support\Events\Log\Deliveries\Delivery, $this>
     */
    public function delivery(): BelongsTo
    {
        return $this->belongsTo(Delivery::class, 'event_log_delivery_id');
    }

    public static function watchdog(): Watchdog\Watchdog
    {
        return resolve(Watchdog\Watchdog::class);
    }
}
