<?php

declare(strict_types=1);

namespace Support\Events\Log\Deliveries;

use Illuminate\Database\Eloquent\Attributes\CollectedBy;
use Illuminate\Database\Eloquent\Attributes\UseEloquentBuilder;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Support\Events\Log\Deliveries\Collection\Deliveries;
use Support\Events\Log\Deliveries\Status\Status;
use Support\Events\Log\DeliveryAttempts\DeliveryAttempt;
use Support\Events\Log\Envelopes\Envelope;
use Support\Events\Log\Relays\Relay;
use Support\Events\Log\Transports\Dispatches\Queues;

/**
 * @property string $id
 * @property string $event_log_relay_id
 * @property string $recipient_id
 * @property string $recipient_type
 * @property string|null $version
 * @property array<string, mixed>|null $payload
 * @property bool $is_deliverable
 * @property int $tries
 * @property \Support\Events\Log\Deliveries\Status\Status $status
 *
 * @phpstan-property \Support\Database\Eloquent\StateMachines\StateMachine<\Support\Events\Log\Deliveries\Status\Status> $status
 */
#[CollectedBy(Deliveries::class)]
#[UseEloquentBuilder(Builder::class)]
#[UseFactory(Factory::class)]
class Delivery extends Model
{
    /** @use HasFactory<Factory> */
    use HasFactory;

    use HasUuids;

    protected $table = 'event_log_deliveries';

    protected $with = ['relay.log', 'recipient'];

    protected $withCount = ['attempts'];

    protected $appends = ['payload', 'is_deliverable'];

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
        'tries' => 1,
        'status' => Status::Pending,
    ];

    protected $fillable = [
        'envelope',
        'tries',
    ];

    protected $casts = [
        'status' => Status::class,
    ];

    public null|string $queue {
        get {
            $key = Queues::on($this->relay->transport)->sending;
            $fallback = config('event_log.queues.'.static::class);

            return match ($key) {
                null => $fallback,
                default => config($key) ?? $fallback,
            };
        }
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\Support\Events\Log\Relays\Relay, $this>
     */
    public function relay(): BelongsTo
    {
        return $this->belongsTo(Relay::class, 'event_log_relay_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\MorphTo<\Illuminate\Database\Eloquent\Model, $this>
     */
    public function recipient(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<\Support\Events\Log\DeliveryAttempts\DeliveryAttempt, $this>
     */
    public function attempts(): HasMany
    {
        return $this->hasMany(DeliveryAttempt::class, 'event_log_delivery_id')->chaperone();
    }

    /**
     * The columns an Envelope deconstructs to for identity matching.
     *
     * @return array<string, mixed>
     */
    public static function identify(Envelope $envelope): array
    {
        return [
            'recipient_type' => $envelope->recipient->getMorphClass(),
            'recipient_id' => $envelope->recipient->getKey(),
            'version' => $envelope->version?->value,
        ];
    }

    public function setRecipientAttribute(Model $model): void
    {
        $this->attributes['recipient_id'] = $model->getKey();
        $this->attributes['recipient_type'] = $model->getMorphClass();
    }

    public function setEnvelopeAttribute(Envelope $envelope): void
    {
        $this->forceFill([
            'recipient' => $envelope->recipient,
            'version' => $envelope->version?->value,
        ]);
    }

    public function setTriesAttribute(null|int $tries): void
    {
        $this->attributes['tries'] = $tries ?? $this->attributes['tries'];
    }

    /**
     * @return \Illuminate\Database\Eloquent\Casts\Attribute<array<string, mixed>|null, never>
     */
    protected function payload(): Attribute
    {
        return Attribute::get(function (): array|null { // @phpstan-ignore return.type
            if ($this->version === null) {
                return $this->relay->log->data;
            }

            $versioned = data_get($this->relay->log->data, $this->version);

            return is_array($versioned) ? $versioned : null;
        });
    }

    /**
     * @return \Illuminate\Database\Eloquent\Casts\Attribute<bool, never>
     */
    protected function isDeliverable(): Attribute
    {
        return Attribute::get(
            fn (): bool => $this->recipient !== null
                && ($this->version === null || data_has($this->relay->log->data, $this->version))
        );
    }

    public static function watchdog(): Watchdog\Watchdog
    {
        return resolve(Watchdog\Watchdog::class);
    }
}
