<?php

declare(strict_types=1);

namespace Support\Events\Log\Logs;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Jsonable;
use Illuminate\Database\ClassMorphViolationException;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Facades\Event;
use JsonSerializable;
use Support\Events\Log\Context\Whitelisted;
use Support\Events\Log\Contracts\Loggable;
use Support\Events\Log\Contracts\Recordable;
use Support\Events\Log\Exceptions\IntegrityViolation;

/**
 * @property string $id
 * @property string $type
 * @property \Support\Events\Log\Context\Whitelisted|null $context
 * @property array<string, mixed>|null $data
 * @property \Support\Events\Log\Contracts\Recordable $event
 * @property string|null $loggable_id
 * @property string|null $loggable_type
 * @property \Carbon\CarbonImmutable $occurred_at
 */
class Log extends Model
{
    use HasUuids;

    protected $table = 'event_logs';

    protected $fillable = [
        'idempotency_key',
        'context',
        'event',
        'occurred_at',
    ];

    protected $casts = [
        'context' => Whitelisted::class,
        'data' => 'array',
        'occurred_at' => 'immutable_datetime',
    ];

    private string $signingKey {
        get => $this->signingKey ??= config('app.key', '');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\MorphTo<\Illuminate\Database\Eloquent\Model, $this>
     */
    public function loggable(): MorphTo
    {
        return $this->morphTo();
    }

    public function setEventAttribute(Recordable $event): void
    {
        $this->attributes['event'] = $this->prepareEvent($event);

        $data = $event->loggable->toLoggable();

        $this->forceFill([
            'type' => $this->event->alias,
            'loggable' => $this->event->loggable,
            'data' => match (true) {
                $data instanceof JsonSerializable => $data->jsonSerialize(),
                $data instanceof Jsonable => json_decode($data->toJson(), true),
                $data instanceof Arrayable => $data->toArray(),
                is_array($data) => $data,
                default => iterator_to_array($data),
            },
        ]);
    }

    public function setLoggableAttribute(Model&Loggable $model): void
    {
        $morph = $model->getMorphClass();

        throw_if($model::class === $morph, ClassMorphViolationException::class, $model);

        $this->attributes['loggable_id'] = $model->getKey();
        $this->attributes['loggable_type'] = $morph;
    }

    public function prepareEvent(Recordable $event): string
    {
        $cloned = tap(
            (clone $event),
            fn (Recordable $cloned) => $cloned->loggable->unsetRelations()->withoutAppends() // unsetRelations() required over withoutRelations()
        );

        $serialized = Event::withoutSerializesModels(fn (): string => serialize($cloned));

        return $this->sign($serialized);
    }

    public function getEventAttribute(string $value): Recordable
    {
        return unserialize(
            $this->verify($value)
        );
    }

    private function sign(string $serialized): string
    {
        return hash_hmac('sha256', $serialized, $this->signingKey).$serialized;
    }

    private function verify(string $value): string
    {
        throw_unless(
            hash_equals(
                substr($value, 0, 64),
                hash_hmac('sha256', $serialized = substr($value, 64), $this->signingKey),
            ),
            new IntegrityViolation,
        );

        return $serialized;
    }
}
