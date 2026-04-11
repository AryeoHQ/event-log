<?php

declare(strict_types=1);

namespace Support\Events\Log\Logs\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\Support\Events\Log\Logs\Entities\Log>
 */
class Factory extends \Illuminate\Database\Eloquent\Factories\Factory
{
    protected $model = \Support\Events\Log\Logs\Log::class;

    public function definition(): array
    {
        return [];
    }
}
