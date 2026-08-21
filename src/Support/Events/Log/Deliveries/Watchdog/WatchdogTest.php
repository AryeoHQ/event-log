<?php

declare(strict_types=1);

namespace Support\Events\Log\Deliveries\Watchdog;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Support\Events\Log\Deliveries\Delivery;
use Support\Events\Log\Deliveries\Status\Status;
use Tests\TestCase;

#[CoversClass(Watchdog::class)]
#[CoversClass(Bite::class)]
final class WatchdogTest extends TestCase
{
    #[Test]
    public function it_bites_deliveries_past_the_grace_period(): void
    {
        $delivery = Delivery::factory()->mqtt()->locked()->createQuietly();
        $delivery->forceFill(['updated_at' => now()->subMinutes(config('event_log.watchdog.grace') + 1)])->saveQuietly();

        Delivery::watchdog()->bite()->now();

        $this->assertSame(Status::Failed, $delivery->fresh()->status->enum);
    }

    #[Test]
    public function it_spares_deliveries_within_the_grace_period(): void
    {
        $delivery = Delivery::factory()->mqtt()->locked()->createQuietly();

        Delivery::watchdog()->bite()->now();

        $this->assertSame(Status::Locked, $delivery->fresh()->status->enum);
    }

    #[Test]
    public function it_spares_a_stale_delivery_once_an_attempt_touches_it(): void
    {
        $delivery = Delivery::factory()->mqtt()->locked()->createQuietly();
        $delivery->forceFill(['updated_at' => now()->subMinutes(config('event_log.watchdog.grace') + 1)])->saveQuietly();

        $delivery->attempts()->createQuietly();

        Delivery::watchdog()->bite()->now();

        $this->assertSame(Status::Locked, $delivery->fresh()->status->enum);
    }
}
