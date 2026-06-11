<?php

declare(strict_types=1);

namespace Support\Events\Log\Logs;

use Illuminate\Database\ClassMorphViolationException;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Support\Events\Log\Concerns\HasEvent;
use Support\Events\Log\Context\Whitelisted;
use Support\Events\Log\Contracts\Loggable;

/**
 * @property string $id
 * @property string $type
 * @property \Support\Events\Log\Context\Whitelisted|null $context
 * @property array<string, mixed>|null $data
 * @property \Support\Events\Log\Contracts\Recordable|\Support\Events\Log\Logs\Integrity\Corrupted|\Support\Events\Log\Logs\Integrity\Tampered $event
 * @property string|null $loggable_id
 * @property string|null $loggable_type
 * @property \Carbon\CarbonImmutable $occurred_at
 */
class Log extends Model
{
    use HasEvent;
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

    /**
     * @return \Illuminate\Database\Eloquent\Relations\MorphTo<\Illuminate\Database\Eloquent\Model, $this>
     */
    public function loggable(): MorphTo
    {
        return $this->morphTo();
    }

    public function setLoggableAttribute(Model&Loggable $model): void
    {
        $morph = $model->getMorphClass();

        throw_if($model::class === $morph, ClassMorphViolationException::class, $model);

        $this->attributes['loggable_id'] = $model->getKey();
        $this->attributes['loggable_type'] = $morph;
    }
}
