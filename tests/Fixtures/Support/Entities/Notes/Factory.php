<?php

declare(strict_types=1);

namespace Tests\Fixtures\Support\Entities\Notes;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\Tests\Fixtures\Support\Entities\Notes\Note>
 */
final class Factory extends \Illuminate\Database\Eloquent\Factories\Factory
{
    protected $model = Note::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'content' => fake()->sentence(),
        ];
    }
}
