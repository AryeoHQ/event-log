<?php

declare(strict_types=1);

namespace Support\Events\Attempts\Factories;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\Support\Events\Attempts\Entities\Attempt>
 */
class Factory extends \Illuminate\Database\Eloquent\Factories\Factory
{
    protected $model = \Support\Events\Attempts\Entities\Attempt::class;

    public function definition(): array
    {
        return [];
    }
}
