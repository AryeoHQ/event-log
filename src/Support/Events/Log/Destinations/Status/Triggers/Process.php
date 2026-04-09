<?php

declare(strict_types=1);

namespace Support\Events\Log\Destinations\Status\Triggers;

use Support\Database\Eloquent\StateMachines\Triggers\Target\Target;
use Support\Database\Eloquent\StateMachines\Triggers\Trigger;
use Support\Events\Log\Contracts\Destinationable;
use Support\Events\Log\Deliveries\Entities\Delivery;
use Support\Events\Log\Destinations\Entities\Destination;
use Support\Events\Log\Manager;

final class Process extends Trigger
{
    #[Target]
    public readonly Destination $destination;

    public function handle(): void
    {
        $destinationable = $this->resolveDestinationable();
        $manager = app(Manager::class);

        /** @var \Support\Events\Log\Contracts\Provider $provider */
        $provider = app($manager->getProvider($destinationable));

        /** @var \Support\Events\Log\Contracts\DestinationProcessor $processor */
        $processor = app($provider->destinationProcessor);

        $deliveries = $processor->deliveries($this->destination);

        $deliveries->each(function (array $deliveryData) {
            /** @var Delivery $delivery */
            $delivery = Delivery::create([
                'event_log_destination_id' => $this->destination->id,
                'payload' => $deliveryData['payload'],
                'delivery_processor' => $deliveryData['delivery_processor'],
                'status' => 'ready',
            ]);

            $delivery->status->prepare()->now();
        });
    }

    /**
     * @return class-string<\Support\Events\Log\Contracts\Destinationable>
     */
    private function resolveDestinationable(): string
    {
        $event = $this->destination->log->event;
        $interfaces = class_implements($event);

        $manager = app(Manager::class);

        return collect($interfaces)
            ->filter(fn (string $interface) => is_subclass_of($interface, Destinationable::class))
            ->first(fn (string $interface) => $manager->getProvider($interface) !== null
                && app($manager->getProvider($interface))->destinationProcessor === $this->destination->destination_processor);
    }
}
