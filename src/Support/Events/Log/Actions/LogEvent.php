<?php

declare(strict_types=1);

namespace Support\Events\Log\Actions;

use Illuminate\Contracts\Broadcasting\ShouldBeUnique;
use Illuminate\Log\Context\Repository;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use RuntimeException;
use Support\Actions\Attributes\DispatchAfterSyncFailed;
use Support\Actions\Concerns\AsAction;
use Support\Actions\Contracts\Action;
use Support\Events\Log\Contracts\Recordable;
use Support\Events\Log\Contracts\RecordableAfterCommit;
use Support\Events\Log\Logs\Log;

#[DispatchAfterSyncFailed]
final class LogEvent implements Action, ShouldBeUnique
{
    use AsAction;

    /** @var int */
    public $tries = 3;

    /** @var list<int> */
    public $backoff = [10, 60, 60 * 5];

    public string $uniqueId {
        get => $this->uniqueId ??= Str::uuid7()->toString();
    }

    public readonly Repository $context;

    public private(set) null|Recordable $original {
        set(mixed $event) {
            throw_if(isset($this->original), RuntimeException::class, 'Cannot modify readonly property '.self::class.'::$original'); // @phpstan-ignore ergebnis.noIsset

            $this->original = $event instanceof Recordable ? $event : null;
        }
    }

    public private(set) null|Recordable $recordable {
        get => $this->recordable ??= $this->preserveOriginal();
        set => $this->preserveOriginal(); // Recordable should only ever be a clone of the original
    }

    public readonly Carbon $occurredAt;

    public function __construct(mixed $event)
    {
        $this->original = $event;
        $this->occurredAt = $this->captureOccurredAt();
        $this->context = $this->captureContext();
    }

    public function handle(): void
    {
        match ($this->recordable instanceof Recordable) {
            true => match ($this->recordable instanceof RecordableAfterCommit) {
                true => $this->createLogAfterCommit(),
                false => $this->createLogWithRollbackProtection(),
            },
            false => null,
        };
    }

    /**
     * Preserve the initial state of the event.
     *
     * We must ensure that the recordable on the event is not serialized when the event
     * log recorded is created. The creation happens immediately, after commit, after
     * rollback, or potentially even on the queue (if initial creation fails we push
     * this action to the queue). It would technically be possible for the event
     * or the recordable to be modified through the lifecycle of the app. We
     * are being extra defensive here out of an abundance of caution.
     */
    private function preserveOriginal(): null|Recordable
    {
        return match ($this->original) {
            null => null,
            default => Event::withoutSerializesModels(
                [$this->original::class],
                fn () => unserialize(serialize(clone $this->original))
            ),
        };
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

    /**
     * Capture the context when the event occurred.
     *
     * Since `event_log` records are only created after the transaction has been
     * committed, there may be a delay between when the event occurred and when
     * it is recorded. By capturing the current context here, we can ensure
     * that the `context` reflects the state when the event occurred.
     */
    private function captureContext(): Repository
    {
        return clone Context::getFacadeRoot();
    }

    private function createLogAfterCommit(): void
    {
        DB::afterCommit(fn () => $this->createLog());
    }

    private function createLogWithRollbackProtection(): void
    {
        $this->createLog();

        // After rollback callbacks ONLY execute if there was a rolled back transaction.
        // If the above was captured in a rollback we want to still ensure the log
        // record is created when it does not implement `RecordableAfterCommit`.
        DB::afterRollBack(fn () => $this->createLog());
    }

    private function createLog(): void
    {
        $this->original->log = $this->recordable->log = Log::createOrFirst(
            ['idempotency_key' => $this->uniqueId],
            [
                'event' => $this->recordable,
                'context' => $this->context,
                'occurred_at' => $this->occurredAt,
            ]
        );
    }
}
