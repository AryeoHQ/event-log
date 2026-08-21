<?php

declare(strict_types=1);

namespace Support\Events\Log\DeliveryAttempts\Watchdog;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Support\Events\Log\DeliveryAttempts\DeliveryAttempt;
use Support\Events\Log\DeliveryAttempts\Status\Status;
use Tests\TestCase;

#[CoversClass(Watchdog::class)]
#[CoversClass(Bite::class)]
final class WatchdogTest extends TestCase
{
    #[Test]
    public function it_bites_attempts_past_the_grace_period(): void
    {
        $attempt = DeliveryAttempt::factory()->mqtt()->locked()->createQuietly();
        $attempt->forceFill(['updated_at' => now()->subMinutes(config('event_log.watchdog.grace') + 1)])->saveQuietly();

        DeliveryAttempt::watchdog()->bite()->now();

        $this->assertSame(Status::Failed, $attempt->fresh()->status->enum);
    }

    #[Test]
    public function it_spares_attempts_within_the_grace_period(): void
    {
        $attempt = DeliveryAttempt::factory()->mqtt()->locked()->createQuietly();

        DeliveryAttempt::watchdog()->bite()->now();

        $this->assertSame(Status::Locked, $attempt->fresh()->status->enum);
    }
}
