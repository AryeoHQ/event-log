<?php

declare(strict_types=1);

namespace Support\Events\Actions;

use Illuminate\Support\Collection;
use Support\Actions\Concerns\AsAction;
use Support\Actions\Contracts\Action;
use Support\Events\Contracts\Destinationable;
use Support\Events\Destinations\Entities\Destination;
use Support\Events\Logs\Entities\Log;

final class RecordDestination implements Action
{
    use AsAction;

    public readonly Log $eventLog;

    public function __construct(Log $eventLog)
    {
        $this->eventLog = $eventLog;
    }

    public function handle(): void
    {
        $destinationables = $this->destinationables($this->eventLog);

        $destinationables->each(function (string $destinationable) {
            $destination = Destination::create([
                'id' => (string) \Illuminate\Support\Str::uuid(),
                'event_log_id' => $this->eventLog->id,
                'destination_processor' => '', // Lookup from Provider
                'status' => 'pending',
            ]);

            RecordDeliveries::make($destination)->dispatch();
        });
    }

    /**
     * @return Collection<int, class-string<Destinationable>>
     */
    public function destinationables(Log $eventLog): Collection
    {
        // Find all interfaces that extend Destinationable
        $event = unserialize($eventLog->event);
        $interfaces = class_implements($event);

        return collect($interfaces)
            ->filter(fn ($interface) => is_subclass_of($interface, Destinationable::class))
            ->values();
    }
}
