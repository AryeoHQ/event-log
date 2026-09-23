<?php

declare(strict_types=1);

namespace Support\Events\Log\Deliveries;

use Illuminate\Database\Eloquent\Factories\Factory as EloquentFactory;
use Support\Events\Database\Eloquent\Swappable\Factories\Concerns\SealsModelName;
use Support\Events\Log\Deliveries\Status\Status;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\Support\Events\Log\Deliveries\Delivery>
 */
class Factory extends EloquentFactory
{
    use SealsModelName;

    final protected $model { get => Delivery::using(); }

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

    final public function succeeded(): self
    {
        return $this->state(['status' => Status::Succeeded]);
    }

    final public function failed(): self
    {
        return $this->state(['status' => Status::Failed]);
    }

    final public function undeliverable(): self
    {
        return $this->state(['status' => Status::Undeliverable]);
    }
}
