<?php

declare(strict_types=1);

namespace Support\Events\Log\Deliveries\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\Support\Events\Log\Deliveries\Entities\Delivery>
 */
class Factory extends \Illuminate\Database\Eloquent\Factories\Factory
{
    protected $model = \Support\Events\Log\Deliveries\Delivery::class;

    public function definition(): array
    {
        return [];
    }
}
