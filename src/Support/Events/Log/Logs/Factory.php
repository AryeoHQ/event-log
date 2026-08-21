<?php

declare(strict_types=1);

namespace Support\Events\Log\Logs;

use Illuminate\Database\Eloquent\Factories\Factory as EloquentFactory;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Str;
use Support\Events\Log\Logs\Status\Status;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\Support\Events\Log\Logs\Log>
 */
final class Factory extends EloquentFactory
{
    protected $model = Log::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'idempotency_key' => Str::uuid7()->toString(),
            'context' => Context::getFacadeRoot(),
            'occurred_at' => now(),
        ];
    }

    public function locked(): self
    {
        return $this->state(fn () => [
            'status' => Status::Locked,
        ]);
    }

    public function failed(): self
    {
        return $this->state(fn () => [
            'status' => Status::Failed,
        ]);
    }
}
