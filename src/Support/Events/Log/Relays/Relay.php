<?php

declare(strict_types=1);

namespace Support\Events\Log\Relays;

use Illuminate\Database\Eloquent\Attributes\CollectedBy;
use Illuminate\Database\Eloquent\Attributes\UseEloquentBuilder;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Support\Events\Log\Deliveries\Delivery;
use Support\Events\Log\Logs\Log;
use Support\Events\Log\Relays\Collection\Relays;
use Support\Events\Log\Relays\Status\Status;
use Support\Events\Log\Transports\Dispatches\Queues;

/**
 * @property string $id
 * @property string $event_log_id
 * @property string $transport
 * @property \Support\Events\Log\Relays\Status\Status $status
 *
 * @phpstan-property \Support\Database\Eloquent\StateMachines\StateMachine<\Support\Events\Log\Relays\Status\Status> $status
 */
#[CollectedBy(Relays::class)]
#[UseEloquentBuilder(Builder::class)]
#[UseFactory(Factory::class)]
class Relay extends Model
{
    /** @use HasFactory<Factory> */
    use HasFactory;

    use HasUuids;

    protected $table = 'event_log_relays';

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
        'event_log_id',
        'transport',
    ];

    protected $casts = [
        'status' => Status::class,
    ];

    public null|string $queue {
        get {
            $key = Queues::on($this->transport)->collecting;
            $fallback = config('event_log.queues.'.static::class);

            return match ($key) {
                null => $fallback,
                default => config($key) ?? $fallback,
            };
        }
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\Support\Events\Log\Logs\Log, $this>
     */
    public function log(): BelongsTo
    {
        return $this->belongsTo(Log::class, 'event_log_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<\Support\Events\Log\Deliveries\Delivery, $this>
     */
    public function deliveries(): HasMany
    {
        return $this->hasMany(Delivery::class, 'event_log_relay_id')->chaperone();
    }

    public static function watchdog(): Watchdog\Watchdog
    {
        return resolve(Watchdog\Watchdog::class);
    }
}
