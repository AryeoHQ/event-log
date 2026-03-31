<?php

declare(strict_types=1);

namespace Support\Events\Logs\Factories;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\Support\Events\Logs\Entities\Log>
 */
class Factory extends \Illuminate\Database\Eloquent\Factories\Factory
{
    protected $model = \Support\Events\Logs\Entities\Log::class;

    public function definition(): array
    {
        return [];
    }
}
