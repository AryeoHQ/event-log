<?php

declare(strict_types=1);

namespace Support\Events\Log\Logs;

use Illuminate\Database\Eloquent\Attributes\CollectedBy;
use Illuminate\Database\Eloquent\Attributes\UseEloquentBuilder;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Support\Events\Log\Concerns\HasEvent;
use Support\Events\Log\Context\Whitelisted;
use Support\Events\Log\Contracts\Loggable;
use Support\Events\Log\Logs\Collection\Logs;
use Support\Events\Log\Logs\Data\Data;
use Support\Events\Log\Logs\Status\Status;
use Support\Events\Log\Relays\Relay;

/**
 * @property string $id
 * @property string $type
 * @property \Support\Events\Log\Context\Whitelisted|null $context
 * @property array<string, mixed>|null $data
 * @property \Support\Events\Log\Contracts\Recordable|\Support\Events\Log\Logs\Integrity\Corrupted|\Support\Events\Log\Logs\Integrity\Tampered $event
 * @property string|null $loggable_id
 * @property string|null $loggable_type
 * @property \Carbon\CarbonImmutable $occurred_at
 * @property \Support\Events\Log\Logs\Status\Status $status
 *
 * @phpstan-property \Support\Database\Eloquent\StateMachines\StateMachine<\Support\Events\Log\Logs\Status\Status> $status
 */
#[CollectedBy(Logs::class)]
#[UseEloquentBuilder(Builder::class)]
#[UseFactory(Factory::class)]
class Log extends Model
{
    use HasEvent;

    /** @use HasFactory<Factory> */
    use HasFactory;

    use HasUuids;

    protected $table = 'event_logs';

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
        'idempotency_key',
        'context',
        'event',
        'occurred_at',
    ];

    protected $casts = [
        'context' => Whitelisted::class,
        'data' => Data::class,
        'occurred_at' => 'immutable_datetime',
        'status' => Status::class,
    ];

    public null|string $queue {
        get => config('event_log.queues.'.static::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<\Support\Events\Log\Relays\Relay, $this>
     */
    public function relays(): HasMany
    {
        return $this->hasMany(Relay::class, 'event_log_id')->chaperone();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\MorphTo<\Illuminate\Database\Eloquent\Model, $this>
     */
    public function loggable(): MorphTo
    {
        return $this->morphTo();
    }

    public function setLoggableAttribute(Model&Loggable $model): void
    {
        $this->attributes['loggable_id'] = $model->getKey();
        $this->attributes['loggable_type'] = $model->getMorphClass();
    }

    public static function watchdog(): Watchdog\Watchdog
    {
        return resolve(Watchdog\Watchdog::class);
    }
}
