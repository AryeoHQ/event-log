<?php

declare(strict_types=1);

namespace Support\Events\Log\Logs\Status\Triggers;

use Illuminate\Support\Collection;
use Support\Database\Eloquent\StateMachines\Triggers\Target\Target;
use Support\Database\Eloquent\StateMachines\Triggers\Trigger;
use Support\Events\Log\Contracts\Destinationable;
use Support\Events\Log\Destinations\Entities\Destination;
use Support\Events\Log\Logs\Entities\Log;
use Support\Events\Log\Manager;

final class Process extends Trigger
{
    #[Target]
    public readonly Log $log;

    public function handle(): void
    {
        $manager = app(Manager::class);
        $destinationables = $this->destinationables();

        $destinationables->each(function (string $destinationable) use ($manager) {
            /** @var \Support\Events\Log\Contracts\Provider $provider */
            $provider = app($manager->getProvider($destinationable));

            /** @var Destination $destination */
            $destination = Destination::create([
                'event_log_id' => $this->log->id,
                'destination_processor' => $provider->destinationProcessor,
                'status' => 'ready',
            ]);

            $destination->status->prepare()->now();
        });
    }

    /**
     * @return \Illuminate\Support\Collection<int, class-string<\Support\Events\Log\Contracts\Destinationable>>
     */
    private function destinationables(): Collection
    {
        // TODO: Should the `Event` be able to answer this question of destinationables instead of this trigger?
        // TODO: Maybe `Manager` should instead?
        $event = $this->log->event;
        $interfaces = class_implements($event);

        return collect($interfaces)
            ->filter(fn ($interface) => is_subclass_of($interface, Destinationable::class))
            ->values();
    }
}
