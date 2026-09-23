<?php

declare(strict_types=1);

namespace Tests\Fixtures\Support\Swappable;

use Illuminate\Database\Eloquent\Factories\Factory as EloquentFactory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\Tests\Fixtures\Support\Swappable\Swappable>
 */
class Factory extends EloquentFactory
{
    protected $model = Swappable::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return ['name' => 'fixture'];
    }
}
