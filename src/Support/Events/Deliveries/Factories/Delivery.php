<?php

declare(strict_types=1);

namespace Support\Events\Deliveries\Factories;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\Support\Events\Deliveries\Entities\Delivery>
 */
class Factory extends \Illuminate\Database\Eloquent\Factories\Factory
{
    protected $model = \Support\Events\Deliveries\Entities\Delivery::class;

    public function definition(): array
    {
        return [];
    }
}
