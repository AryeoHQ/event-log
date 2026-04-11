<?php

declare(strict_types=1);

namespace Support\Events\Log;

use Illuminate\Support\Facades\Event;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Support\Events\Log\Logs\Log;
use Tests\Fixtures\Support\Entities\Articles\Article;
use Tests\Fixtures\Support\Entities\Articles\Events\Created;
use Tests\TestCase;

#[CoversClass(Dispatcher::class)]
final class DispatcherTest extends TestCase
{
    #[Test]
    public function it_records_event(): void
    {
        $model = Article::factory()->create();

        $this->assertCount(1, Log::all());

        tap(Log::first(), function (Log $log) use ($model) {
            $this->assertInstanceOf(
                $model->dispatchesEvents()['created'],
                $log->event
            );
            $this->assertTrue($model->is($log->entity));
        });
    }

    #[Test]
    public function it_retains_listener_pipeline(): void
    {
        Event::listen(Created::class, function (Created $event) {
            $this->assertTrue(true);
        });

        Article::factory()->create();
    }

    #[Test]
    public function it_records_event_before_listeners(): void
    {
        Event::listen(Created::class, function (Created $event) {
            $this->assertNotEmpty(Log::all());
        });

        $this->assertEmpty(Log::all());

        Article::factory()->create();
    }

    #[Test]
    public function it_ignores_non_recordable_events(): void
    {
        $model = Article::withoutEvents(
            fn () => Article::factory()->create()
        );

        $model->touch();

        $this->assertEmpty(Log::all());
    }
}
