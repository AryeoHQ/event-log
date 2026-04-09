<?php

declare(strict_types=1);

namespace Support\Events\Log\Destinations\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\Support\Events\Log\Destinations\Entities\Destination>
 */
class Factory extends \Illuminate\Database\Eloquent\Factories\Factory
{
    protected $model = \Support\Events\Log\Destinations\Entities\Destination::class;

    public function definition(): array
    {
        return [];
    }
}
