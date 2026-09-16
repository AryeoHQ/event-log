<?php

declare(strict_types=1);

namespace Support\Events\Log\Provides;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Support\Stringable;
use ReflectionClass;
use ReflectionNamedType;
use ReflectionProperty;
use Support\Events\Log\Alias\Alias;
use Support\Events\Log\Alias\Exceptions\NotDefined as AliasNotDefined;
use Support\Events\Log\Concerns\SerializesModels;
use Support\Events\Log\Contracts\Loggable;
use Support\Events\Log\IdentifiesLoggable\Exceptions\MultipleDefined;
use Support\Events\Log\IdentifiesLoggable\Exceptions\NotDefined;
use Support\Events\Log\IdentifiesLoggable\Exceptions\NotLoggable;
use Support\Events\Log\IdentifiesLoggable\IdentifiesLoggable;
use Support\Events\Log\Logs\Log;

trait HasLoggable
{
    use Dispatchable;
    use SerializesModels;

    public Log $log;

    /**
     * This is PURPOSELY NOT memoized to guarantee it
     * is not included when the event is serialized.
     */
    public Model&Loggable $loggable {
        get => $this->{$this->loggableProperty};
    }

    /**
     * Start this as an empty string so serialization always includes it.
     *
     * The `SerializesModels` trait reads each property through reflection
     * when it serializes the event. That read runs the get hook, which
     * resolves the property name and stores it. So the resolved name is
     * locked into the payload, even if nothing read this property before.
     *
     * This matters on the queue. An event can wait in the queue while the
     * code changes. Because the name was locked in at serialize time, the
     * event still points at the correct property after the code changes.
     */
    public private(set) string $loggableProperty = '' {
        get => $this->loggableProperty ?: $this->loggableProperty = with( // @phpstan-ignore ternary.shortNotAllowed
            collect((new ReflectionClass($this))->getProperties())
                ->filter(fn (ReflectionProperty $property): bool => (bool) $property->getAttributes(IdentifiesLoggable::class))
                ->tap(fn ($properties) => throw_unless($properties->isNotEmpty(), NotDefined::class, $this))
                ->tap(fn ($properties) => throw_unless($properties->count() === 1, MultipleDefined::class, $this))
                ->first(),
            function (ReflectionProperty $property) {
                throw_unless($property->getType() instanceof ReflectionNamedType, NotLoggable::class, $this);
                $type = $property->getType()->getName();
                throw_unless(is_subclass_of($type, Loggable::class) && is_subclass_of($type, Model::class), NotLoggable::class, $this);

                return $property->getName();
            }
        );
    }

    public Stringable $alias {
        get => $this->alias ??= str(
            collect((new ReflectionClass($this))->getAttributes(Alias::class))
                ->first()?->newInstance()->name ?? throw AliasNotDefined::on(static::class)
        );
    }

    public Stringable $uniqueAlias {
        get => $this->uniqueAlias ??= $this->alias->replaceLast('.', ".{$this->loggable->getKey()}.");
    }
}
