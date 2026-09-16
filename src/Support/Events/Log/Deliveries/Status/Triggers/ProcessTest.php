<?php

declare(strict_types=1);

namespace Support\Events\Log\Deliveries\Status\Triggers;

use Illuminate\Support\Facades\Event;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Support\Events\Log\Deliveries\Delivery;
use Support\Events\Log\DeliveryAttempts\DeliveryAttempt;
use Support\Events\Log\Envelopes\Envelope;
use Tests\Fixtures\Support\Entities\Recordable\Recordable;
use Tests\Fixtures\Support\Entities\Relayable\Relayable;
use Tests\Fixtures\Support\Mqtt\Collecting\Events\NeedsEnvelopes;
use Tests\Fixtures\Support\Mqtt\PayloadVersion;
use Tests\TestCase;

#[CoversClass(Process::class)]
final class ProcessTest extends TestCase
{
    #[Test]
    public function it_creates_a_delivery_attempt(): void
    {
        Relayable::factory()->create()->announceToLog();

        $this->assertSame(1, DeliveryAttempt::count());
    }

    #[Test]
    public function it_succeeds_the_delivery(): void
    {
        Succeed::fake();

        Relayable::factory()->create()->announceToLog();

        Succeed::assertFired();
    }

    #[Test]
    public function it_fires_disqualify_trigger_when_run_directly(): void
    {
        Disqualify::fake();

        // Drive process DIRECTLY through ->now() to exercise $this->fail()
        // under a placeholder SyncJob (empty payload).
        $delivery = Delivery::factory()->mqtt()
            ->state(['envelope' => Envelope::make(recipient: Recordable::factory()->create(), version: PayloadVersion::V2)])
            ->locked()
            ->createQuietly();

        $delivery->status->process()->now();

        Disqualify::assertFired();
    }

    #[Test]
    public function it_fires_disqualify_trigger_without_an_attempt_when_the_version_is_not_present(): void
    {
        Disqualify::fake();

        Event::forget(NeedsEnvelopes::class);
        Event::listen(NeedsEnvelopes::class, function (NeedsEnvelopes $event): void {
            $event->add(Envelope::make(recipient: Recordable::factory()->create(), version: PayloadVersion::V2));
        });

        Relayable::factory()->create()->announceToLog();

        Disqualify::assertFired();
        $this->assertSame(0, DeliveryAttempt::count());
    }

    #[Test]
    public function it_fires_disqualify_trigger_without_an_attempt_when_the_recipient_has_been_deleted(): void
    {
        Disqualify::fake();

        $recipient = Recordable::factory()->create();

        Event::forget(NeedsEnvelopes::class);
        Event::listen(NeedsEnvelopes::class, function (NeedsEnvelopes $event) use ($recipient): void {
            $event->add(Envelope::make(recipient: $recipient));

            $recipient->delete();
        });

        Relayable::factory()->create()->announceToLog();

        Disqualify::assertFired();
        $this->assertSame(0, DeliveryAttempt::count());
    }
}
