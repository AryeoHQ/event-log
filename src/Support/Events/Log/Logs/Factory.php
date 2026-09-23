<?php

declare(strict_types=1);

namespace Support\Events\Log\Logs;

use Illuminate\Database\Eloquent\Factories\Factory as EloquentFactory;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Str;
use Support\Events\Database\Eloquent\Swappable\Factories\Concerns\SealsModelName;
use Support\Events\Log\Logs\Status\Status;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\Support\Events\Log\Logs\Log>
 */
class Factory extends EloquentFactory
{
    use SealsModelName;

    final protected $model { get => Log::using(); }

    /**
     * @return array<string, mixed>
     */
    final public function definition(): array
    {
        return [
            'idempotency_key' => Str::uuid7()->toString(),
            'context' => Context::getFacadeRoot(),
            'occurred_at' => now(),
        ];
    }

    final public function locked(): self
    {
        return $this->state(['status' => Status::Locked]);
    }

    final public function failed(): self
    {
        return $this->state(['status' => Status::Failed]);
    }
}
