<?php

declare(strict_types=1);

namespace Support\Events\Log\Transportables;

use Illuminate\Database\Eloquent\Factories\Factory as EloquentFactory;
use Support\Events\Database\Eloquent\Swappable\Factories\Concerns\SealsModelName;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\Support\Events\Log\Transportables\Transportable>
 */
class Factory extends EloquentFactory
{
    use SealsModelName;

    final protected $model { get => Transportable::using(); }

    /**
     * @return array<string, mixed>
     */
    final public function definition(): array
    {
        return [
            'id' => fake()->unique()->slug(),
            'class' => fake()->unique()->numerify('App\\Events\\Event####'),
            'transports' => [],
        ];
    }
}
