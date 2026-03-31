<?php

declare(strict_types=1);

namespace Support\Events\Destinations\Factories;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\Support\Events\Destinations\Entities\Destination>
 */
class Factory extends \Illuminate\Database\Eloquent\Factories\Factory
{
    protected $model = \Support\Events\Destinations\Entities\Destination::class;

    public function definition(): array
    {
        return [];
    }
}
