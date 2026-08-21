<?php

declare(strict_types=1);

namespace Tests\Fixtures\Support\Entities\Recordable;

use Tests\Fixtures\Support\Entities\NonRecordable\NonRecordable;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\Tests\Fixtures\Support\Entities\Recordable\Recordable>
 */
final class Factory extends \Illuminate\Database\Eloquent\Factories\Factory
{
    protected $model = Recordable::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => fake()->sentence(),
            'non_recordable_id' => fn () => NonRecordable::factory(),
        ];
    }
}
