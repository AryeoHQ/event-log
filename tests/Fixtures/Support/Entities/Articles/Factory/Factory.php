<?php

declare(strict_types=1);

namespace Tests\Fixtures\Support\Entities\Articles\Factory;

use Tests\Fixtures\Support\Entities\Articles\Article;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<Article>
 */
final class Factory extends \Illuminate\Database\Eloquent\Factories\Factory
{
    /**
     * @var class-string<Article>
     */
    protected $model = Article::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            //
        ];
    }
}
