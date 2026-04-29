<?php

declare(strict_types=1);

namespace Tests\Fixtures\Support\Entities\Tags;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\Tests\Fixtures\Support\Entities\Tags\Tag>
 */
final class Factory extends \Illuminate\Database\Eloquent\Factories\Factory
{
    protected $model = Tag::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => fake()->word(),
        ];
    }
}
