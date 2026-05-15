<?php

declare(strict_types=1);

namespace Support\Events\Log\Dispatcher;

use Illuminate\Support\Facades\Event;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversTrait;
use PHPUnit\Framework\Attributes\Test;
use Support\Events\Log\Dispatcher\Concerns\ForwardsCalls;
use Support\Events\Log\Logs\Log;
use Tests\Fixtures\Support\Entities\Articles\Article;
use Tests\Fixtures\Support\Entities\Articles\Events\Updated;
use Tests\Fixtures\Support\Entities\Articles\Events\Viewed;
use Tests\TestCase;

#[CoversClass(Dispatcher::class)]
#[CoversTrait(ForwardsCalls::class)]
final class DispatcherTest extends TestCase
{
    #[Test]
    public function it_records_recordable_events(): void
    {
        $article = Article::factory()->create();

        Event::dispatch(new Updated($article));

        $this->assertCount(1, Log::all());
    }

    #[Test]
    public function it_ignores_non_recordable_events(): void
    {
        $article = Article::factory()->create();

        Event::dispatch(new Viewed($article));

        $this->assertEmpty(Log::all());
    }

    #[Test]
    public function it_retains_listener_pipeline(): void
    {
        $called = false;

        Event::listen(Updated::class, function () use (&$called) {
            $called = true;
        });

        Event::dispatch(new Updated(Article::factory()->create()));

        $this->assertTrue($called);
    }

    #[Test]
    public function it_records_event_before_listeners(): void
    {
        $logsAtListenerTime = null;

        Event::listen(Updated::class, function () use (&$logsAtListenerTime) {
            $logsAtListenerTime = Log::count();
        });

        Event::dispatch(new Updated(Article::factory()->create()));

        $this->assertSame(1, $logsAtListenerTime);
    }
}
