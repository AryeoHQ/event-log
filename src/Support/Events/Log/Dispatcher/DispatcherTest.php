<?php

declare(strict_types=1);

namespace Support\Events\Log\Dispatcher;

use Illuminate\Support\Facades\Event;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversTrait;
use PHPUnit\Framework\Attributes\Test;
use Support\Events\Log\Dispatcher\Concerns\ForwardsCalls;
use Support\Events\Log\Logs\Log;
use Tests\Fixtures\Support\Entities\Recordable\Events\Creating;
use Tests\Fixtures\Support\Entities\Recordable\Events\Updated;
use Tests\Fixtures\Support\Entities\Recordable\Recordable;
use Tests\Fixtures\Tooling\EventLog\RecordableWithoutAlias;
use Tests\TestCase;

#[CoversClass(Dispatcher::class)]
#[CoversTrait(ForwardsCalls::class)]
final class DispatcherTest extends TestCase
{
    #[Test]
    public function it_records_recordable_events(): void
    {
        Recordable::factory()->create()->announceToLog();

        $this->assertCount(1, Log::all());
    }

    #[Test]
    public function it_ignores_non_recordable_events(): void
    {
        $recordable = Recordable::factory()->create();

        Event::dispatch(new Creating($recordable));

        $this->assertEmpty(Log::all());
    }

    #[Test]
    public function it_retains_listener_pipeline(): void
    {
        $called = false;

        Event::listen(Updated::class, function () use (&$called) {
            $called = true;
        });

        Recordable::factory()->create()->announceToLog();

        $this->assertTrue($called);
    }

    #[Test]
    public function it_records_event_before_listeners(): void
    {
        $logsAtListenerTime = null;

        Event::listen(Updated::class, function () use (&$logsAtListenerTime) {
            $logsAtListenerTime = Log::count();
        });

        Recordable::factory()->create()->announceToLog();

        $this->assertSame(1, $logsAtListenerTime);
    }

    #[Test]
    public function it_retains_listener_pipeline_when_recording_fails(): void
    {
        $called = false;

        Event::listen(RecordableWithoutAlias::class, function () use (&$called) {
            $called = true;
        });

        Event::dispatch(new RecordableWithoutAlias(Recordable::factory()->create()));

        $this->assertTrue($called);
        $this->assertCount(0, Log::all());
    }

    #[Test]
    public function it_records_recordable_halting_events(): void
    {
        $recordable = Recordable::factory()->create();

        Event::until(new Updated($recordable));

        $this->assertCount(1, Log::all());
    }

    #[Test]
    public function it_ignores_non_recordable_halting_events(): void
    {
        $recordable = Recordable::factory()->create();

        Event::until(new Creating($recordable));

        $this->assertEmpty(Log::all());
    }

    #[Test]
    public function it_retains_halting_listener_pipeline(): void
    {
        $called = false;

        Event::listen(Updated::class, function () use (&$called) {
            $called = true;

            return false;
        });

        $result = Event::until(new Updated(Recordable::factory()->create()));

        $this->assertTrue($called);
        $this->assertFalse($result);
    }
}
