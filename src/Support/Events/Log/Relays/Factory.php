<?php

declare(strict_types=1);

namespace Support\Events\Log\Relays;

use Illuminate\Database\Eloquent\Factories\Factory as EloquentFactory;
use Support\Events\Database\Eloquent\Swappable\Factories\Concerns\SealsModelName;
use Support\Events\Log\Relays\Status\Status;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\Support\Events\Log\Relays\Relay>
 */
class Factory extends EloquentFactory
{
    use SealsModelName;

    final protected $model { get => Relay::using(); }

    /**
     * @return array<string, mixed>
     */
    final public function definition(): array
    {
        return [];
    }

    final public function locked(): self
    {
        return $this->state(['status' => Status::Locked]);
    }

    final public function processed(): self
    {
        return $this->state(['status' => Status::Processed]);
    }

    final public function failed(): self
    {
        return $this->state(['status' => Status::Failed]);
    }
}
