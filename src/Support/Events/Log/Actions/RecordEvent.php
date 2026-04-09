<?php

declare(strict_types=1);

namespace Support\Events\Log\Actions;

use Illuminate\Support\Facades\Context;
use Support\Actions\Concerns\AsAction;
use Support\Actions\Contracts\Action;
use Support\Events\Log\Contracts\Recordable;
use Support\Events\Log\Logs\Entities\Log;

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
        if (! ($this->event instanceof Recordable)) {
            return;
        }

        // TODO: Actor and Subject need to be relationships
        // TODO: Mutators for actor and subject to accept a model
        // TODO: We need all of our models to have a morphmap
        // TODO: We need to confirm the `Model` isn't serialized on initial storage
        // TODO: We need to confirm we are calling `now()` and `dispatch()` appropriately
        /** @var Log $log */
        $log = Log::create([
            'event' => $this->event,
            'actor_id' => Context::get(config('event_log.key_to_the_actor')),
            'actor_type' => null,
            'subject_id' => Context::get(config('event_log.key_to_the_subject')),
            'subject_type' => null,
        ]);

        $log->status->prepare()->now();
    }
}
