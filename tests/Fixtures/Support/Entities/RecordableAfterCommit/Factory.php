<?php

declare(strict_types=1);

namespace Tests\Fixtures\Support\Entities\RecordableAfterCommit;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\Tests\Fixtures\Support\Entities\RecordableAfterCommit\RecordableAfterCommit>
 */
final class Factory extends \Illuminate\Database\Eloquent\Factories\Factory
{
    protected $model = RecordableAfterCommit::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [];
    }
}
