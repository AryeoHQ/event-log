<?php

declare(strict_types=1);

namespace Support\Events\Log\Relays;

use Illuminate\Database\Eloquent\Factories\Factory as EloquentFactory;
use Support\Events\Log\Relays\Status\Status;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\Support\Events\Log\Relays\Relay>
 */
final class Factory extends EloquentFactory
{
    protected $model = Relay::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [];
    }

    public function locked(): self
    {
        return $this->state(['status' => Status::Locked]);
    }

    public function processed(): self
    {
        return $this->state(['status' => Status::Processed]);
    }

    public function failed(): self
    {
        return $this->state(['status' => Status::Failed]);
    }
}
