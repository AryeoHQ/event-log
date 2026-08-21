<?php

declare(strict_types=1);

namespace Support\Events\Log\Relays;

use Orchestra\Testbench\Attributes\WithConfig;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Support\Events\Log\Deliveries\Delivery;
use Support\Events\Log\Logs\Log;
use Tests\Fixtures\Support\Entities\Relayable\Relayable;
use Tests\Fixtures\Support\Mqtt\Mqtt;
use Tests\Fixtures\Support\Mqtt\Queued;
use Tests\TestCase;

#[CoversClass(Relay::class)]
final class RelayTest extends TestCase
{
    #[Test]
    public function it_cascades_from_log_to_relay_to_delivery(): void
    {
        Relayable::factory()->create()->announceToLog();

        $this->assertCount(1, Relay::all());
        $this->assertCount(1, Delivery::all());

        $log = Log::first();
        $relay = Relay::first();
        $delivery = Delivery::first();

        $this->assertSame($log->id, $relay->event_log_id);
        $this->assertSame(Mqtt::class, $relay->transport);
        $this->assertSame($relay->id, $delivery->event_log_relay_id);
        $this->assertNotNull($delivery->recipient_id);
        $this->assertSame($log->data, $delivery->payload);
    }

    #[Test]
    #[WithConfig('mqtt.queues.collecting', 'collecting')]
    public function it_resolves_the_queue_from_the_transport_config_key(): void
    {
        $this->assertSame('collecting', Relay::factory()->mqtt(Queued::class)->createQuietly()->queue);
    }

    #[Test]
    #[WithConfig('event_log.queues.'.Relay::class, 'relays')]
    public function it_falls_back_to_the_layer_queue_when_the_transport_config_key_is_unset(): void
    {
        $this->assertSame('relays', Relay::factory()->mqtt(Queued::class)->createQuietly()->queue);
    }

    #[Test]
    #[WithConfig('event_log.queues.'.Relay::class, 'relays')]
    public function it_falls_back_to_the_layer_queue_when_the_transport_has_no_queues(): void
    {
        $this->assertSame('relays', Relay::factory()->mqtt(Mqtt::class)->createQuietly()->queue);
    }

    #[Test]
    public function it_resolves_no_queue_when_nothing_is_configured(): void
    {
        $this->assertNull(Relay::factory()->mqtt(Mqtt::class)->createQuietly()->queue);
    }
}
