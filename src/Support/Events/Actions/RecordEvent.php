<?php

declare(strict_types=1);

namespace Support\Events\Actions;

use Illuminate\Support\Facades\Context;
use Support\Actions\Concerns\AsAction;
use Support\Actions\Contracts\Action;
use Support\Events\Contracts\Destinationable;
use Support\Events\Contracts\Recordable;
use Support\Events\Logs\Entities\Log;

final class RecordEvent implements Action
{
    use AsAction;

    public readonly mixed $event;

    public function __construct(mixed $event)
    {
        $this->event = $event;
    }

    public function handle(): void
    {
        if (!($this->event instanceof Recordable)) {
            return;
        }

        $eventLog = Log::create([
            'id' => (string) \Illuminate\Support\Str::uuid(),
            'type' => $this->event->name,
            'entity_id' => null,
            'entity_type' => get_class($this->event),
            'event' => serialize($this->event),
            'occurred_at' => now(),
            'actor_id' => Context::get(config('event_log.key_to_the_actor')),
            'actor_type' => null,
            'subject_id' => Context::get(config('event_log.key_to_the_subject')),
            'subject_type' => null,
        ]);

        $this->prepareDestinations($eventLog, $this->event);
    }

    private function prepareDestinations(Log $eventLog, mixed $event): void
    {
        if ($event instanceof Destinationable) {
            RecordDestination::make($eventLog)->dispatch();
        }
    }
}
