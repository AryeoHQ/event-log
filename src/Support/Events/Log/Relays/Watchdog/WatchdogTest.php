<?php

declare(strict_types=1);

namespace Support\Events\Log\Relays\Watchdog;

use Orchestra\Testbench\Attributes\WithConfig;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Support\Events\Log\Relays\Relay;
use Support\Events\Log\Relays\Status\Status;
use Tests\TestCase;

#[CoversClass(Watchdog::class)]
#[CoversClass(Bite::class)]
final class WatchdogTest extends TestCase
{
    #[Test]
    #[WithConfig('event_log.queues.'.Relay::class, 'relays')]
    public function it_bites_on_the_layer_queue(): void
    {
        $this->assertSame('relays', Relay::watchdog()->bite()->queue);
    }

    #[Test]
    public function it_bites_relays_past_the_grace_period(): void
    {
        $relay = Relay::factory()->mqtt()->locked()->createQuietly();
        $relay->forceFill(['updated_at' => now()->subMinutes(config('event_log.watchdog.grace') + 1)])->saveQuietly();

        Relay::watchdog()->bite()->now();

        $this->assertSame(Status::Failed, $relay->fresh()->status->enum);
    }

    #[Test]
    public function it_spares_relays_within_the_grace_period(): void
    {
        $relay = Relay::factory()->mqtt()->locked()->createQuietly();

        Relay::watchdog()->bite()->now();

        $this->assertSame(Status::Locked, $relay->fresh()->status->enum);
    }
}
