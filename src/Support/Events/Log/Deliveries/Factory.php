<?php

declare(strict_types=1);

namespace Support\Events\Log\Deliveries;

use Illuminate\Database\Eloquent\Factories\Factory as EloquentFactory;
use Support\Events\Log\Deliveries\Status\Status;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\Support\Events\Log\Deliveries\Delivery>
 */
final class Factory extends EloquentFactory
{
    protected $model = Delivery::class;

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

    public function succeeded(): self
    {
        return $this->state(['status' => Status::Succeeded]);
    }

    public function failed(): self
    {
        return $this->state(['status' => Status::Failed]);
    }

    public function undeliverable(): self
    {
        return $this->state(['status' => Status::Undeliverable]);
    }
}
