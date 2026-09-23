<?php

declare(strict_types=1);

namespace Support\Events\Log\DeliveryAttempts;

use Illuminate\Database\Eloquent\Attributes\CollectedBy;
use Illuminate\Database\Eloquent\Attributes\UseEloquentBuilder;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Support\Events\Database\Eloquent\Swappable\Models\Concerns\SupportsSwapping;
use Support\Events\Database\Eloquent\Swappable\Models\Contracts\Swappable;
use Support\Events\Log\Deliveries\Delivery;
use Support\Events\Log\DeliveryAttempts\Collection\DeliveryAttempts;
use Support\Events\Log\DeliveryAttempts\Status\Status;
use Support\Events\Log\Transports\Dispatches\Sending\Results\Result;

/**
 * @property string $id
 * @property string $delivery_id
 * @property \Support\Events\Log\Deliveries\Delivery $delivery
 * @property \Support\Events\Log\DeliveryAttempts\Status\Status $status
 * @property \Support\Events\Log\Transports\Dispatches\Sending\Results\Result|null $result
 * @property \Carbon\CarbonImmutable $attempted_at
 *
 * @phpstan-property \Support\Database\Eloquent\StateMachines\StateMachine<\Support\Events\Log\DeliveryAttempts\Status\Status> $status
 */
#[CollectedBy(DeliveryAttempts::class)]
#[UseEloquentBuilder(Builder::class)]
#[UseFactory(Factory::class)]
class DeliveryAttempt extends Model implements Swappable
{
    use HasUuids {
        getKeyType as private uuidKeyType;
        getIncrementing as private uuidIncrementing;
    }

    /** @use SupportsSwapping<Factory, Builder> */
    use SupportsSwapping;

    final public $incrementing = false;

    final protected $table = 'event_log_delivery_attempts';

    final protected $primaryKey = 'id';

    final protected $keyType = 'string';

    final public function getKeyType(): string
    {
        return $this->uuidKeyType();
    }

    final public function getIncrementing(): bool
    {
        return $this->uuidIncrementing();
    }

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
        'result',
        'attempted_at',
    ];

    protected $casts = [
        'attempted_at' => 'immutable_datetime',
        'result' => Result::class,
        'status' => Status::class,
    ];

    public null|string $queue {
        get => $this->delivery->queue;
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\Support\Events\Log\Deliveries\Delivery, $this>
     */
    final public function delivery(): BelongsTo
    {
        return $this->belongsTo(Delivery::using(), 'event_log_delivery_id');
    }

    final public static function watchdog(): Watchdog\Watchdog
    {
        return resolve(Watchdog\Watchdog::class);
    }
}
