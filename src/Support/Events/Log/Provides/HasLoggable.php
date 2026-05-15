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
     * This is PURPOSELY initialized as an empty string to guarantee
     * it will be included when the event is serialized as PHP will
     * automatically include all initialized properties. However,
     * we never want the value to actually be an empty string.
     * Since we are defining a get hook, that is ALWAYS called
     * no matter how a property is accessed. Since serialization
     * necessarily reads "initialized" properties we can ensure
     * that even if this property was never accessed during the
     * lifecycle of the application the evaluation will be run.
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
        get => $this->uniqueAlias ??= str(
            $this->alias->explode('.')->join(".{$this->loggable->getKey()}.")
        );
    }
}
