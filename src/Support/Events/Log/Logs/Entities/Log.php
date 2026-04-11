<?php

declare(strict_types=1);

namespace Support\Events\Log\Logs\Entities;

use Illuminate\Database\Eloquent\Attributes\CollectedBy;
use Illuminate\Database\Eloquent\Attributes\UseEloquentBuilder;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Facades\Event;
use Support\Entities\Contracts\Entity;
use Support\Entities\References\Concerns\AsEntity;
use Support\Events\Log\Contracts\Recordable;
use Support\Events\Log\Logs\Builder\Builder;
use Support\Events\Log\Logs\Collection\Logs;
use Support\Events\Log\Logs\Events;
use Support\Events\Log\Logs\Factory\Factory;
use Support\Events\Log\Logs\Policy\Policy;
use Support\Events\Log\Logs\Status\Status;

/**
 * @use HasFactory<Factory>
 *
 * @property (\Support\Events\Log\Logs\Status\Status & \Support\Database\Eloquent\StateMachines\StateMachine) $status
 *
 * @phpstan-property \Support\Database\Eloquent\StateMachines\StateMachine<\Support\Events\Log\Logs\Status\Status> $status
 */
#[CollectedBy(Logs::class)]
#[UseEloquentBuilder(Builder::class)]
#[UseFactory(Factory::class)]
#[UsePolicy(Policy::class)]
class Log extends Model implements Entity
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

    protected $table = 'event_logs';

    protected $fillable = [
        'event',
        'actor_id',
        'actor_type',
        'subject_id',
        'subject_type',
        'occurred_at',
    ];

    protected $casts = [
        'occurred_at' => 'datetime',
        'status' => Status::class,
    ];

    protected $attributes = [
        'status' => Status::Ready,
    ];

    protected static function booted(): void
    {
        static::creating(
            fn (Log $log) => $log->occurred_at ?? $log->forceFill([
                'occurred_at' => now(),
            ])
        );
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\MorphTo<\Illuminate\Database\Eloquent\Model, $this>
     */
    public function entity(): MorphTo
    {
        return $this->morphTo();
    }

    public function setEntityAttribute(Entity $entity): void
    {
        $this->attributes['entity_id'] = $entity->getKey();
        $this->attributes['entity_type'] = $entity->getMorphClass();
    }

    // TODO: Should we use a caster instead of mutators?
    public function setEventAttribute(Recordable $event): void
    {
        $this->attributes['type'] = $event->alias;
        $this->attributes['event'] = Event::withoutSerializesModels(fn () => serialize(clone $event));
        $this->entity = $event->entity;
    }

    public function getEventAttribute(string $value): Recordable
    {
        return unserialize($value);
    }
}
