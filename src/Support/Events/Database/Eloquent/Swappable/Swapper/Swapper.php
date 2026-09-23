<?php

declare(strict_types=1);

namespace Support\Events\Database\Eloquent\Swappable\Swapper;

use Closure;
use Illuminate\Container\Attributes\Scoped;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use ReflectionClass;
use Support\Events\Database\Eloquent\Swappable\Models\Concerns\SupportsSwapping;
use Support\Events\Database\Eloquent\Swappable\Models\Contracts\Swappable;
use Support\Events\Database\Eloquent\Swappable\Swapper\Exceptions\Invalid;
use Support\Events\Database\Eloquent\Swappable\Swapper\Exceptions\MissingAttribute;
use Support\Events\Database\Eloquent\Swappable\Swapper\Exceptions\NotSwappable;

#[Scoped]
final class Swapper
{
    /**
     * Maps where the consumer's value takes precedence on a
     * conflicting key, so a subclass can swap in its own event class.
     */
    public const array CONSUMER_MAPS = ['dispatchesEvents'];

    /**
     * Maps where the package's value takes precedence on a
     * conflicting key, so the status cast and default stay intact.
     */
    public const array PACKAGE_MAPS = ['casts', 'attributes'];

    public const array LISTS = ['fillable', 'with', 'withCount', 'appends', 'touches'];

    /**
     * @var array<class-string, class-string>
     */
    private array $swaps = [];

    /**
     * @param  class-string<\Support\Events\Database\Eloquent\Swappable\Models\Contracts\Swappable>  $swappable
     * @param  class-string<\Support\Events\Database\Eloquent\Swappable\Models\Contracts\Swappable>  $with
     */
    public function replace(string $swappable, string $with): void
    {
        throw_unless($this->isSwappable($swappable), NotSwappable::class, $swappable);
        throw_unless(is_a($with, $swappable, true), Invalid::class, $with, $swappable);

        $this->swaps[$swappable] = $with;
    }

    /**
     * @param  class-string<\Support\Events\Database\Eloquent\Swappable\Models\Contracts\Swappable>  $swappable
     * @return class-string<\Support\Events\Database\Eloquent\Swappable\Models\Contracts\Swappable>
     */
    public function using(string $swappable): string
    {
        return $this->swaps[$swappable] ?? $swappable;
    }

    /**
     * @param  class-string  $model
     * @param  class-string  $attribute
     */
    public function validateAttribute(string $model, string $attribute): void
    {
        $actual = $this->declared($model, $attribute);
        $expected = $this->declared($this->origin($model), $attribute);

        throw_unless(
            is_a($actual, $expected, true),
            Invalid::class, $actual, $expected
        );
    }

    /**
     * @param  class-string  $model
     * @param  class-string  $attribute
     * @return class-string
     */
    public function declared(string $model, string $attribute): string
    {
        $attributes = new ReflectionClass($model)->getAttributes($attribute);

        throw_if($attributes === [], MissingAttribute::class, $model, $attribute);

        return $attributes[0]->getArguments()[0];
    }

    /**
     * The contract alone is not enough, since a model could declare it without
     * the trait that makes swapping work.
     *
     * @param  class-string  $model
     */
    public function isSwappable(string $model): bool
    {
        return is_a($model, Swappable::class, true)
            && in_array(SupportsSwapping::class, class_uses_recursive($model), true);
    }

    /**
     * The model this package ships, which a swapped-in subclass extends.
     *
     * @param  class-string  $model
     * @return class-string
     */
    public function origin(string $model): string
    {
        return collect(array_values(class_parents($model)))
            ->last(fn (string $parent): bool => is_a($parent, Swappable::class, true))
            ?? $model;
    }

    public function consolidate(Model $model): void
    {
        $consolidated = $this->mergeConsumerMaps($model)
            ->merge($this->mergePackageMaps($model))
            ->merge($this->appendLists($model));

        Closure::bind(
            fn () => $consolidated->each(fn (array $value, string $property) => $model->{$property} = $value),
            null,
            $model::class,
        )();

        $this->validateEvents($model);
    }

    /** @phpstan-ignore missingType.generics */
    private function mergeConsumerMaps(Model $model): Collection
    {
        $reflection = new ReflectionClass($model);

        return collect(self::CONSUMER_MAPS)->mapWithKeys(
            fn (string $property) => [
                $property => collect($this->inherited($model::class, $property))
                    ->merge($reflection->getProperty($property)->getValue($model))
                    ->all(),
            ]
        );
    }

    /** @phpstan-ignore missingType.generics */
    private function mergePackageMaps(Model $model): Collection
    {
        $originDefaults = (new ReflectionClass($this->origin($model::class)))->getDefaultProperties();

        return collect(self::PACKAGE_MAPS)->mapWithKeys(
            fn (string $property) => [
                $property => collect($this->inherited($model::class, $property))
                    ->merge($originDefaults[$property] ?? [])
                    ->all(),
            ]
        );
    }

    /** @phpstan-ignore missingType.generics */
    private function appendLists(Model $model): Collection
    {
        $reflection = new ReflectionClass($model);

        return collect(self::LISTS)->mapWithKeys(
            fn (string $property) => [
                $property => collect($this->inherited($model::class, $property))
                    ->concat($reflection->getProperty($property)->getValue($model))
                    ->unique()
                    ->values()
                    ->all(),
            ]
        );
    }

    public function validateEvents(Model $model): void
    {
        /** @var array<string, class-string> $expected */
        $expected = new ReflectionClass($this->origin($model::class))->getDefaultProperties()['dispatchesEvents'] ?? [];

        collect($model->dispatchesEvents())
            ->filter(
                fn (string $actual, string $key): bool => array_key_exists($key, $expected) && ! is_a($actual, $expected[$key], true)
            )
            ->each(
                fn (string $actual, string $key) => throw new Invalid($actual, $expected[$key])
            );
    }

    /**
     * Subclasses override properties of parents when they are declared, so
     * we merge everything declared in the hierarchy.
     *
     * @param  class-string  $model
     * @return array<array-key, mixed>
     */
    public function inherited(string $model, string $property): array
    {
        return collect(array_reverse([$model, ...array_values(class_parents($model))]))
            ->map(fn (string $class): array => new ReflectionClass($class)->getDefaultProperties()[$property] ?? [])
            ->collapse()
            ->all();
    }
}
