<?php

declare(strict_types=1);

namespace Support\Events\Log\Relays\Status\Triggers;

use Illuminate\Support\Facades\Event;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversTrait;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;
use Support\Events\Log\Deliveries\Delivery;
use Support\Events\Log\Envelopes\Envelope;
use Support\Events\Log\Relays\Relay;
use Support\Events\Log\Relays\Status\Status as RelayStatus;
use Support\Events\Log\Transports\Dispatches\Collecting\Provides\CollectsEnvelopes;
use Tests\Fixtures\Support\Entities\Recordable\Recordable;
use Tests\Fixtures\Support\Entities\Relayable\Relayable;
use Tests\Fixtures\Support\Mqtt\Collecting\Events\NeedsEnvelopes;
use Tests\Fixtures\Support\Mqtt\WithoutTries;
use Tests\Fixtures\Tooling\EventLog\RelayableWithoutDispatches;
use Tests\TestCase;

#[CoversClass(Process::class)]
#[CoversTrait(CollectsEnvelopes::class)]
final class ProcessTest extends TestCase
{
    #[Test]
    public function it_fires_collecting_event_and_creates_deliveries(): void
    {
        Relayable::factory()->create()->announceToLog();

        $this->assertCount(1, Delivery::all());

        $delivery = Delivery::first();
        $this->assertSame(Relay::first()->id, $delivery->event_log_relay_id);
        $this->assertNotNull($delivery->recipient_id);
        $this->assertSame((new Recordable)->getMorphClass(), $delivery->recipient_type);
    }

    #[Test]
    public function it_stores_tries_from_transport(): void
    {
        Relayable::factory()->create()->announceToLog();

        $this->assertSame(3, Delivery::first()->tries);
    }

    #[Test]
    public function it_defaults_tries_when_the_transport_has_no_tries(): void
    {
        $relay = Relay::factory()->mqtt(WithoutTries::class)->createQuietly();

        $relay->status->lock()->now();

        $this->assertSame(1, Delivery::first()->tries);
    }

    #[Test]
    public function it_creates_a_delivery_per_envelope(): void
    {
        Event::forget(NeedsEnvelopes::class);
        Event::listen(NeedsEnvelopes::class, function (NeedsEnvelopes $event): void {
            $event->add(Envelope::make(recipient: Recordable::factory()->create()));
            $event->add(Envelope::make(recipient: Recordable::factory()->create()));
        });

        Relayable::factory()->create()->announceToLog();

        $this->assertCount(2, Delivery::all());
        $this->assertSame(2, Delivery::query()->distinct('recipient_id')->count()); // @phpstan-ignore staticMethod.dynamicCall, staticMethod.dynamicCall
    }

    #[Test]
    public function it_does_not_duplicate_deliveries_when_the_job_runs_twice(): void
    {
        $recipient = Recordable::factory()->create();

        Event::forget(NeedsEnvelopes::class);
        Event::listen(NeedsEnvelopes::class, function (NeedsEnvelopes $event) use ($recipient): void {
            $event->add(Envelope::make(recipient: $recipient));
        });

        $relay = Relay::factory()->mqtt()->locked()->createQuietly();

        $relay->status->process()->now();

        // Simulate an at-least-once redelivery: the same job runs again on a
        // row whose status has already advanced. A redelivered worker sees
        // the pre-transition Locked state, so replay handle() from there.
        $relay->forceFill(['status' => RelayStatus::Locked])->syncOriginal();
        $relay->status->process()->now();

        $this->assertCount(1, Delivery::all());
    }

    #[Test]
    public function it_creates_no_deliveries_when_no_envelopes_are_gathered(): void
    {
        Fail::fake();

        Event::forget(NeedsEnvelopes::class);

        Relayable::factory()->create()->announceToLog();

        Fail::assertNotFired();
        $this->assertCount(0, Delivery::all());
    }

    #[Test]
    public function it_fires_fail_trigger_when_gathering_throws(): void
    {
        Fail::fake();

        Event::forget(NeedsEnvelopes::class);
        Event::listen(NeedsEnvelopes::class, fn () => throw new RuntimeException);

        Relayable::factory()->create()->announceToLog();

        Fail::assertFired();
        $this->assertCount(0, Delivery::all());
    }

    #[Test]
    public function it_fires_fail_trigger_when_the_transport_has_no_dispatches(): void
    {
        Fail::fake();

        $relay = Relay::factory()->mqtt(RelayableWithoutDispatches::class)->createQuietly();

        rescue(fn () => $relay->status->lock()->now(), null, false);

        Fail::assertFired();
    }
}
