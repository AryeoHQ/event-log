<?php

declare(strict_types=1);

namespace Support\Events\Log\Transportables;

use Illuminate\Database\Eloquent\Factories\Factory as EloquentFactory;
use Illuminate\Support\Collection;
use Support\Events\Database\Eloquent\Swappable\Factories\Concerns\SealsModelName;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\Support\Events\Log\Transportables\Transportable>
 */
class Factory extends EloquentFactory
{
    use SealsModelName;

    final protected $model { get => Transportable::using(); }

    /** @var Collection<int, array<string, mixed>> */
    private static Collection $remaining;

    /**
     * @return array<string, mixed>
     */
    final public function definition(): array
    {
        self::$remaining ??= resolve(Discovery::class)->transportables->shuffle();

        return match (self::$remaining->isEmpty()) {
            true => throw new \OverflowException('All discovered transportables have been used.'),
            false => self::$remaining->pop(),
        };
    }
}
