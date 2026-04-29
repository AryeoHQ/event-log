<?php

declare(strict_types=1);

namespace Tests\Fixtures\Support\Entities\Articles;

use Tests\Fixtures\Support\Entities\Categories\Category;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\Tests\Fixtures\Support\Entities\Articles\Article>
 */
final class Factory extends \Illuminate\Database\Eloquent\Factories\Factory
{
    protected $model = Article::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => fake()->sentence(),
            'category_id' => fn () => Category::factory(),
        ];
    }
}
