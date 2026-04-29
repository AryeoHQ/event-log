<?php

declare(strict_types=1);

namespace Support\Events\Log\Actions;

use Illuminate\Contracts\Broadcasting\ShouldBeUnique;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Orchestra\Testbench\Attributes\WithEnv;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;
use Support\Events\Log\Logs\Log;
use Tests\Fixtures\Support\Entities\Articles\Article;
use Tests\Fixtures\Support\Entities\Articles\Events\Updated;
use Tests\Fixtures\Support\Entities\Articles\Events\Updating;
use Tests\Fixtures\Support\Entities\Articles\Events\Viewed;
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
        $action = LogEvent::make(new Updated(Article::factory()->make()));

        $this->assertSame(3, $action->tries);
        $this->assertSame([10, 60, 60 * 5], $action->backoff);
    }

    #[Test]
    public function it_provides_uuid7_unique_id(): void
    {
        $action = LogEvent::make(new Updated(Article::factory()->make()));

        $this->assertTrue(
            Str::isUuid($action->uniqueId, 7),
            'Expected '.class_basename(LogEvent::class).'::$uniqueId to be a valid UUIDv7 string, got: '.$action->uniqueId
        );
    }

    #[Test]
    public function it_stores_a_clone_of_the_original_event(): void
    {
        $action = LogEvent::make($event = new Updated(Article::factory()->create()));

        $this->assertInstanceOf($event::class, $action->original);
        $this->assertInstanceOf($event::class, $action->recordable);
        $this->assertSame($action->original::class, $action->recordable::class);
        $this->assertNotSame($action->original, $action->recordable);
    }

    #[Test]
    public function it_creates_an_event_log_when_event_is_recordable(): void
    {
        $event = new Updating(Article::factory()->create());

        LogEvent::make($event)->now();

        $this->assertCount(1, Log::all());
    }

    #[Test]
    public function it_creates_a_single_event_log_when_recordable_is_raised_in_a_transaction(): void
    {
        $article = Article::factory()->create();

        DB::transaction(fn () => LogEvent::make(new Updating($article))->now());

        $this->assertCount(1, Log::all());
    }

    #[Test]
    public function it_creates_an_event_log_when_event_is_recordable_after_commit(): void
    {
        $event = new Updated(Article::factory()->create());

        LogEvent::make($event)->now();

        $this->assertCount(1, Log::all());
    }

    #[Test]
    public function it_defers_recordable_after_commit_until_transaction_commits(): void
    {
        $article = Article::factory()->create();

        DB::transaction(function () use ($article) {
            LogEvent::make(new Updated($article))->now();

            $this->assertCount(0, Log::all());
        });

        $this->assertCount(1, Log::all());
    }

    #[Test]
    public function it_does_not_create_an_event_log_when_event_is_not_recordable(): void
    {
        $event = new Viewed(Article::factory()->create());

        LogEvent::make($event)->now();

        $this->assertEmpty(Log::all());
    }

    #[Test]
    public function it_records_type_from_alias(): void
    {
        $event = new Updated(Article::factory()->create());

        LogEvent::make($event)->now();

        $this->assertSame($event->alias->toString(), Log::first()->type);
    }

    #[Test]
    public function it_records_loggable_morph_from_recordable(): void
    {
        $article = Article::factory()->create();

        LogEvent::make(new Updated($article))->now();

        $log = Log::first();

        $this->assertSame($article->getKey(), $log->loggable_id);
        $this->assertSame($article->getMorphClass(), $log->loggable_type);
    }

    #[Test]
    #[WithEnv('EVENT_LOG_CONTEXT_WHITELIST', 'allowed')]
    public function it_records_context(): void
    {
        Context::add('allowed', true);
        Context::add('not_allowed', false);

        LogEvent::make(new Updated(Article::factory()->create()))->now();

        $this->assertSame(['allowed' => true], Log::first()->context->toArray());
    }

    #[Test]
    public function it_round_trips_the_event_blob(): void
    {
        $article = Article::factory()->create();
        $event = new Updated($article);

        LogEvent::make($event)->now();

        $hydrated = Log::first()->event;

        $this->assertInstanceOf(Updated::class, $hydrated);
        $this->assertSame($article->getKey(), $hydrated->article->getKey());
    }

    #[Test]
    public function it_records_when_raised_in_failed_transaction(): void
    {
        $article = Article::factory()->create();

        try {
            DB::transaction(function () use ($article) {
                LogEvent::make(new Updating($article))->now();

                throw new RuntimeException('simulated failure');
            });
        } catch (RuntimeException) {
            // expected
        }

        $this->assertCount(1, Log::all());
    }

    #[Test]
    public function it_does_not_record_when_recordable_after_commit_raised_in_failed_transaction(): void
    {
        $article = Article::factory()->create();

        try {
            DB::transaction(function () use ($article) {
                LogEvent::make(new Updated($article))->now();

                throw new RuntimeException('simulated failure');
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
        $event = new Updated(Article::factory()->make());
        Event::listen('eloquent.creating: '.Log::class, fn ($event) => throw new RuntimeException);

        rescue(fn () => LogEvent::make($event)->now(), null, false);

        Queue::assertPushed(LogEvent::class, function (LogEvent $action) use ($event) {
            return $action->original === $event;
        });
    }

    #[Test]
    public function it_stores_a_clone_of_the_original_context(): void
    {
        $action = LogEvent::make($event = new Updated(Article::factory()->create()));

        $this->assertInstanceOf(Context::getFacadeRoot()::class, $action->context);
        $this->assertNotSame(Context::getFacadeRoot(), $action->context);
    }
}
