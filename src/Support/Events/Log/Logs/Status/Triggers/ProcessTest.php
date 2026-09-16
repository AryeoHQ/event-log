<?php

declare(strict_types=1);

namespace Support\Events\Log\Logs\Status\Triggers;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;
use Support\Events\Log\Logs\Log;
use Support\Events\Log\Logs\Status\Status as LogStatus;
use Support\Events\Log\Relays\Relay;
use Tests\Fixtures\Support\Amqp\Amqp;
use Tests\Fixtures\Support\Entities\Recordable\Events\Updated;
use Tests\Fixtures\Support\Entities\Recordable\Recordable;
use Tests\Fixtures\Support\Entities\Relayable\Events\Multiplexed;
use Tests\Fixtures\Support\Entities\Relayable\Relayable;
use Tests\Fixtures\Support\Mqtt\Mqtt;
use Tests\TestCase;

#[CoversClass(Process::class)]
final class ProcessTest extends TestCase
{
    #[Test]
    public function it_does_not_create_relays_for_non_relayable_events(): void
    {
        $log = Log::factory()
            ->state(['event' => new Updated(Recordable::factory()->create())])
            ->locked()
            ->createQuietly();

        $log->status->process()->now();

        $this->assertCount(0, Relay::all());
    }

    #[Test]
    public function it_creates_relays_for_relayable_events(): void
    {
        $log = Log::factory()->mqtt()->locked()->createQuietly();

        $log->status->process()->now();

        $this->assertCount(1, Relay::all());

        $relay = Relay::first();
        $this->assertTrue($log->is($relay->log));
        $this->assertSame(Mqtt::class, $relay->transport);
    }

    #[Test]
    public function it_creates_a_relay_per_transport(): void
    {
        $log = Log::factory()
            ->state(['event' => new Multiplexed(Relayable::factory()->create())])
            ->locked()
            ->createQuietly();

        $log->status->process()->now();

        $this->assertCount(2, Relay::all());
        $this->assertEqualsCanonicalizing(
            [Amqp::class, Mqtt::class],
            Relay::pluck('transport')->all(),
        );
    }

    #[Test]
    public function it_does_not_duplicate_relays_when_the_job_runs_twice(): void
    {
        $log = Log::factory()->mqtt()->locked()->createQuietly();

        $log->status->process()->now();

        // Simulate an at-least-once redelivery: the same job runs again on a
        // row whose status has already advanced. A redelivered worker sees
        // the pre-transition Locked state, so replay handle() from there.
        $log->forceFill(['status' => LogStatus::Locked])->syncOriginal();
        $log->status->process()->now();

        $this->assertCount(1, Relay::all());
    }

    #[Test]
    public function it_fires_fail_trigger_on_failure(): void
    {
        Fail::fake();

        Relay::creating(fn (): bool => throw new RuntimeException);

        $log = Log::factory()->mqtt()->locked()->createQuietly();

        rescue(fn () => $log->status->process()->now(), null, false);

        Fail::assertFired();
        $this->assertCount(0, Relay::all());
    }

    #[Test]
    public function it_fires_compromise_trigger_when_the_event_is_tampered(): void
    {
        Compromise::fake();

        $log = Log::factory()->mqtt()->locked()->createQuietly();

        Log::withoutEvents(fn () => $log->newQuery()->toBase()->where('id', $log->id)->update([
            'event' => with(
                $log->getRawOriginal('event'),
                fn (string $raw) => substr($raw, 0, 64).'TAMPERED'.substr($raw, 72)
            ),
        ]));

        $log = $log->fresh();
        rescue(fn () => $log->status->process()->now(), null, false);

        Compromise::assertFired();
        $this->assertCount(0, Relay::all());
    }

    #[Test]
    public function it_fires_compromise_trigger_when_the_event_is_corrupted(): void
    {
        Compromise::fake();

        $log = Log::factory()->mqtt()->locked()->createQuietly();

        Log::withoutEvents(fn () => $log->newQuery()->toBase()->where('id', $log->id)->update([
            'event' => '!!!not-base64!!!',
        ]));

        $log = $log->fresh();
        rescue(fn () => $log->status->process()->now(), null, false);

        Compromise::assertFired();
        $this->assertCount(0, Relay::all());
    }
}
