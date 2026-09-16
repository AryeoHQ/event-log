<?php

declare(strict_types=1);

namespace Tests\Fixtures\Support\Entities\NonRecordable;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\Tests\Fixtures\Support\Entities\NonRecordable\NonRecordable>
 */
final class Factory extends \Illuminate\Database\Eloquent\Factories\Factory
{
    protected $model = NonRecordable::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [];
    }
}
