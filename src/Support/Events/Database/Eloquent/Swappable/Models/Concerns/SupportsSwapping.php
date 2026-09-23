<?php

declare(strict_types=1);

namespace Support\Events\Database\Eloquent\Swappable\Models\Concerns;

use Support\Events\Database\Eloquent\Swappable\Swapper\Facades\Swapper;

/**
 * @template TFactory of \Illuminate\Database\Eloquent\Factories\Factory
 * @template TBuilder
 */
trait SupportsSwapping
{
    use InheritsDeclarations;

    /** @use SealsBuilder<TBuilder> */
    use SealsBuilder;

    use SealsCollection;

    /** @use SealsFactory<TFactory> */
    use SealsFactory;

    use SealsSchema;

    /**
     * @param  class-string<static>  $model
     */
    final public static function use(string $model): void
    {
        Swapper::replace(static::class, $model);
    }

    /**
     * @return class-string<static>
     */
    final public static function using(): string
    {
        return Swapper::using(static::class); // @phpstan-ignore return.type
    }
}
