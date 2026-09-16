<?php

declare(strict_types=1);

namespace Support\Events\Log\Actions;

use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Orchestra\Testbench\Attributes\WithConfig;
use Orchestra\Testbench\Attributes\WithEnv;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;
use Support\Events\Log\Logs;
use Support\Events\Log\Logs\Log;
use Tests\Fixtures\Support\Entities\Recordable\Events\Creating;
use Tests\Fixtures\Support\Entities\Recordable\Events\Updated;
use Tests\Fixtures\Support\Entities\Recordable\Recordable;
use Tests\Fixtures\Support\Entities\RecordableAfterCommit\RecordableAfterCommit;
use Tests\TestCase;

#[CoversClass(LogEvent::class)]
final class LogEventTest extends TestCase
{
    #[Test]
    public function it_implements_should_be_unique(): void
    {
        $this->assertContains(
            ShouldBeUnique::class,
            class_implements(LogEvent::class),
        );
    }

    #[Test]
    public function it_retries_with_exponential_backoff(): void
    {
        $action = LogEvent::make(new Updated(Recordable::factory()->make()));

        $this->assertSame(3, $action->tries);
        $this->assertSame([10, 60, 60 * 5], $action->backoff);
    }

    #[Test]
    public function it_provides_uuid7_unique_id(): void
    {
        $action = LogEvent::make(new Updated(Recordable::factory()->make()));

        $this->assertTrue(
            Str::isUuid($action->uniqueId, 7),
            'Expected '.class_basename(LogEvent::class).'::$uniqueId to be a valid UUIDv7 string, got: '.$action->uniqueId
        );
    }

    #[Test]
    public function it_creates_one_log_when_run_twice(): void
    {
        $action = LogEvent::make(new Updated(Recordable::factory()->create()));

        $action->now();
        $action->now();

        $this->assertCount(1, Log::all());
    }

    #[Test]
    public function it_stores_a_clone_of_the_original_event(): void
    {
        $action = LogEvent::make($event = new Updated(Recordable::factory()->create()));

        $this->assertInstanceOf($event::class, $action->original);
        $this->assertInstanceOf($event::class, $action->recordable);
        $this->assertSame($action->original::class, $action->recordable::class);
        $this->assertNotSame($action->original, $action->recordable);
    }

    #[Test]
    public function it_creates_an_event_log_when_event_is_recordable(): void
    {
        Recordable::factory()->create()->announceToLog();

        $this->assertCount(1, Log::all());
    }

    #[Test]
    public function it_creates_a_single_event_log_when_recordable_is_raised_in_a_transaction(): void
    {
        $recordable = Recordable::factory()->create();

        DB::transaction(fn () => $recordable->announceToLog());

        $this->assertCount(1, Log::all());
    }

    #[Test]
    public function it_creates_an_event_log_when_event_is_recordable_after_commit(): void
    {
        RecordableAfterCommit::factory()->create()->announceToLog();

        $this->assertCount(1, Log::all());
    }

    #[Test]
    public function it_defers_recordable_after_commit_until_transaction_commits(): void
    {
        $recordableAfterCommit = RecordableAfterCommit::factory()->create();

        DB::transaction(function () use ($recordableAfterCommit) {
            $recordableAfterCommit->announceToLog();

            $this->assertCount(0, Log::all());
        });

        $this->assertCount(1, Log::all());
    }

    #[Test]
    public function it_does_not_create_an_event_log_when_event_is_not_recordable(): void
    {
        $event = new Creating(Recordable::factory()->create());

        LogEvent::make($event)->now();

        $this->assertEmpty(Log::all());
    }

    #[Test]
    public function it_records_type_from_alias(): void
    {
        Recordable::factory()->create()->announceToLog();

        $this->assertSame('recordable.updated', Log::first()->type);
    }

    #[Test]
    public function it_records_loggable_morph_from_recordable(): void
    {
        $recordable = Recordable::factory()->create();

        $recordable->announceToLog();

        $log = Log::first();

        $this->assertSame($recordable->getKey(), $log->loggable_id);
        $this->assertSame($recordable->getMorphClass(), $log->loggable_type);
    }

    #[Test]
    #[WithEnv('EVENT_LOG_CONTEXT_WHITELIST', 'allowed')]
    public function it_records_context(): void
    {
        Context::add('allowed', true);
        Context::add('not_allowed', false);

        Recordable::factory()->create()->announceToLog();

        $this->assertSame(['allowed' => true], Log::first()->context->toArray());
    }

    #[Test]
    public function it_round_trips_the_event_blob(): void
    {
        $recordable = Recordable::factory()->create();

        $recordable->announceToLog();

        $hydrated = Log::first()->event;

        $this->assertInstanceOf(Updated::class, $hydrated);
        $this->assertSame($recordable->getKey(), $hydrated->recordable->getKey());
    }

    #[Test]
    public function it_records_when_raised_in_failed_transaction(): void
    {
        $recordable = Recordable::factory()->create();

        try {
            DB::transaction(function () use ($recordable) {
                $recordable->announceToLog();

                throw new RuntimeException;
            });
        } catch (RuntimeException) {
            // expected
        }

        $this->assertCount(1, Log::all());
    }

    #[Test]
    public function it_does_not_record_when_recordable_after_commit_raised_in_failed_transaction(): void
    {
        $recordableAfterCommit = RecordableAfterCommit::factory()->create();

        try {
            DB::transaction(function () use ($recordableAfterCommit) {
                $recordableAfterCommit->announceToLog();

                throw new RuntimeException;
            });
        } catch (RuntimeException) {
            // expected
        }

        $this->assertCount(0, Log::all());
    }

    #[Test]
    public function it_dispatches_to_the_queue_when_failed(): void
    {
        Queue::fake(LogEvent::class);
        $event = new Updated(Recordable::factory()->make());
        Event::listen(Logs\Events\Creating::class, fn () => throw new RuntimeException);

        rescue(fn () => LogEvent::make($event)->dispatchAfterFailed()->now(), null, false);

        Queue::assertPushed(LogEvent::class, function (LogEvent $action) use ($event) {
            return $action->original === $event;
        });
    }

    #[Test]
    #[WithConfig('event_log.queues.'.Log::class, 'logs')]
    public function it_runs_on_the_log_layer_queue(): void
    {
        $this->assertSame('logs', LogEvent::make(new Updated(Recordable::factory()->make()))->queue);
    }

    #[Test]
    public function it_stores_a_clone_of_the_original_context(): void
    {
        $action = LogEvent::make($event = new Updated(Recordable::factory()->create()));

        $this->assertInstanceOf(Context::getFacadeRoot()::class, $action->context);
        $this->assertNotSame(Context::getFacadeRoot(), $action->context);
    }

    #[Test]
    public function it_preserves_unique_id_when_re_dispatched_after_failure(): void
    {
        Queue::fake(LogEvent::class);
        Event::listen(Logs\Events\Creating::class, fn () => throw new RuntimeException);

        $action = LogEvent::make(new Updated(Recordable::factory()->make()));

        rescue(fn () => $action->dispatchAfterFailed()->now(), null, false);

        Queue::assertPushed(LogEvent::class, fn (LogEvent $pushed) => $pushed->uniqueId === $action->uniqueId);
    }
}
