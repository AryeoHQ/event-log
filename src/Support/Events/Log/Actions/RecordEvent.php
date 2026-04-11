<?php

declare(strict_types=1);

namespace Support\Events\Log\Actions;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Support\Actions\Contracts\Action;
use Support\Actions\Concerns\AsAction;
use Illuminate\Support\Facades\Context;
use Support\Events\Log\Logs\Entities\Log;
use Support\Events\Log\Contracts\Recordable;
use Support\Events\Log\Contracts\RecordableAfterCommit;

final class RecordEvent implements Action
{
    use AsAction;

    private null|Recordable $event {
        set(mixed $event) => match ($event instanceof Recordable) {
            true => $this->preserveEvent($event),
            false => null,
        };
    }

    private readonly Carbon $occurredAt;

    public function __construct(mixed $event)
    {
        $this->event = $event;
        $this->occurredAt = $this->captureOccurredAt();
    }

    // TODO: Actor and Subject need to be relationships

    // TODO: Mutators for actor and subject to accept a model
    // TODO: We need all of our models to have a morphmap
    // TODO: We need to confirm the `Model` isn't serialized on initial storage
    // TODO: We need to confirm we are calling `now()` and `dispatch()` appropriately
    // TODO: Only the prepares that run synchronously should transition Phase::Before
    public function handle(): void
    {
        // Recordable (ing) / RecordableAfterCommit (ed)
        if (! ($this->event instanceof Recordable)) {
            return;
        }

        match (true) {
            $this->event instanceof RecordableAfterCommit => DB::afterCommit(fn () => $this->writeToDatabase()),
            default => $this->writeToDatabase()
        };
    }

    /**
     * Preserve the initial state of the event.
     *
     * We only record `event_log` records after the transaction has been committed.
     * However, we do not control if Listeners mutate the event before we record.
     * Most importantly they could mutate the event's entity, causing us to
     * store the post-event version of the entity instead of the initial
     * version. Cloning the event preserves the initial version.
     */
    private function preserveEvent(Recordable $event): Recordable
    {
        return clone $event;
    }

    /**
     * Capture the moment the event occurred.
     *
     * Since `event_log` records are only created after the transaction has been
     * committed, there may be a delay between when the event occurred and when
     * it is recorded. By capturing the current timestamp here, we can ensure
     * that the `occurred_at` timestamp reflects when the event occurred.
     */
    private function captureOccurredAt(): Carbon
    {
        return now();
    }

    private function writeToDatabase(): void
    {
        DB::afterCommit(function () {
            /** @var Log $log */
            $log = Log::create([
                'event' => $this->event,
                'occurred_at' => $this->occurredAt,
                'actor_id' => Context::get(config('event_log.key_to_the_actor')),
                'actor_type' => null,
                'subject_id' => Context::get(config('event_log.key_to_the_subject')),
                'subject_type' => null,
            ]);

            $log->status->prepare()->now();
        });
    }
}
