<?php

declare(strict_types=1);

namespace Support\Events\Log\Deliveries;

use Illuminate\Support\Facades\Event;
use Orchestra\Testbench\Attributes\WithConfig;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Support\Events\Log\Envelopes\Envelope;
use Support\Events\Log\Logs\Log;
use Tests\Fixtures\Support\Entities\Recordable\Recordable;
use Tests\Fixtures\Support\Entities\Relayable\Relayable;
use Tests\Fixtures\Support\Mqtt\Collecting\Events\NeedsEnvelopes;
use Tests\Fixtures\Support\Mqtt\PayloadVersion;
use Tests\Fixtures\Support\Mqtt\Queued;
use Tests\TestCase;

#[CoversClass(Delivery::class)]
final class DeliveryTest extends TestCase
{
    #[Test]
    public function it_resolves_the_full_data_when_version_is_null(): void
    {
        Relayable::factory()->create()->announceToLog();

        $delivery = Delivery::first();

        $this->assertNull($delivery->version);
        $this->assertSame(Log::first()->data, $delivery->payload);
    }

    #[Test]
    public function it_resolves_the_versioned_slice_of_data(): void
    {
        Event::forget(NeedsEnvelopes::class);
        Event::listen(NeedsEnvelopes::class, function (NeedsEnvelopes $event): void {
            $event->add(Envelope::make(recipient: Recordable::factory()->create(), version: PayloadVersion::V1));
        });

        Relayable::factory()->create()->announceToLog();

        $delivery = Delivery::first();

        $this->assertSame(PayloadVersion::V1->value, $delivery->version);
        $this->assertSame(data_get(Log::first()->data, PayloadVersion::V1->value), $delivery->payload);
    }

    #[Test]
    public function it_resolves_a_null_payload_when_the_version_is_not_present(): void
    {
        Event::forget(NeedsEnvelopes::class);
        Event::listen(NeedsEnvelopes::class, function (NeedsEnvelopes $event): void {
            $event->add(Envelope::make(recipient: Recordable::factory()->create(), version: PayloadVersion::V2));
        });

        Relayable::factory()->create()->announceToLog();

        $delivery = Delivery::first();

        $this->assertSame(PayloadVersion::V2->value, $delivery->version);
        $this->assertNull($delivery->payload);
    }

    #[Test]
    public function it_is_deliverable_when_the_version_is_null(): void
    {
        Relayable::factory()->create()->announceToLog();

        $this->assertTrue(Delivery::first()->is_deliverable);
    }

    #[Test]
    public function it_is_deliverable_when_the_version_is_present(): void
    {
        Event::forget(NeedsEnvelopes::class);
        Event::listen(NeedsEnvelopes::class, function (NeedsEnvelopes $event): void {
            $event->add(Envelope::make(recipient: Recordable::factory()->create(), version: PayloadVersion::V1));
        });

        Relayable::factory()->create()->announceToLog();

        $this->assertTrue(Delivery::first()->is_deliverable);
    }

    #[Test]
    public function it_is_not_deliverable_when_the_version_is_not_present(): void
    {
        Event::forget(NeedsEnvelopes::class);
        Event::listen(NeedsEnvelopes::class, function (NeedsEnvelopes $event): void {
            $event->add(Envelope::make(recipient: Recordable::factory()->create(), version: PayloadVersion::V2));
        });

        Relayable::factory()->create()->announceToLog();

        $this->assertFalse(Delivery::first()->is_deliverable);
    }

    #[Test]
    public function it_serializes_without_throwing_when_the_version_is_not_present(): void
    {
        Event::forget(NeedsEnvelopes::class);
        Event::listen(NeedsEnvelopes::class, function (NeedsEnvelopes $event): void {
            $event->add(Envelope::make(recipient: Recordable::factory()->create(), version: PayloadVersion::V2));
        });

        Relayable::factory()->create()->announceToLog();

        $serialized = Delivery::first()->toArray();

        $this->assertNull($serialized['payload']);
        $this->assertFalse($serialized['is_deliverable']);
    }

    #[Test]
    public function it_is_not_deliverable_when_the_recipient_has_been_deleted(): void
    {
        Event::forget(NeedsEnvelopes::class);
        Event::listen(NeedsEnvelopes::class, function (NeedsEnvelopes $event): void {
            $event->add(Envelope::make(recipient: Recordable::factory()->create()));
        });

        Relayable::factory()->create()->announceToLog();

        $delivery = Delivery::first();
        $delivery->recipient->delete();

        $this->assertFalse($delivery->fresh()->is_deliverable);
    }

    #[Test]
    #[WithConfig('mqtt.queues.sending', 'sending')]
    public function it_resolves_the_queue_from_the_transport_config_key(): void
    {
        $this->assertSame('sending', Delivery::factory()->mqtt(Queued::class)->createQuietly()->queue);
    }

    #[Test]
    #[WithConfig('event_log.queues.'.Delivery::class, 'deliveries')]
    public function it_falls_back_to_the_layer_queue_when_the_transport_config_key_is_unset(): void
    {
        $this->assertSame('deliveries', Delivery::factory()->mqtt(Queued::class)->createQuietly()->queue);
    }

    #[Test]
    #[WithConfig('event_log.queues.'.Delivery::class, 'deliveries')]
    public function it_falls_back_to_the_layer_queue_when_the_transport_has_no_queues(): void
    {
        $this->assertSame('deliveries', Delivery::factory()->mqtt()->createQuietly()->queue);
    }

    #[Test]
    public function it_resolves_no_queue_when_nothing_is_configured(): void
    {
        $this->assertNull(Delivery::factory()->mqtt()->createQuietly()->queue);
    }
}
