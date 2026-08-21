<?php

declare(strict_types=1);

namespace Support\Events\Log\Logs\Watchdog;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Support\Events\Log\Logs\Log;
use Support\Events\Log\Logs\Status\Status;
use Tests\TestCase;

#[CoversClass(Watchdog::class)]
#[CoversClass(Bite::class)]
final class WatchdogTest extends TestCase
{
    #[Test]
    public function it_bites_logs_past_the_grace_period(): void
    {
        $log = Log::factory()->mqtt()->locked()->createQuietly();
        $log->forceFill(['updated_at' => now()->subMinutes(config('event_log.watchdog.grace') + 1)])->saveQuietly();

        Log::watchdog()->bite()->now();

        $this->assertSame(Status::Failed, $log->fresh()->status->enum);
    }

    #[Test]
    public function it_spares_logs_within_the_grace_period(): void
    {
        $log = Log::factory()->mqtt()->locked()->createQuietly();

        Log::watchdog()->bite()->now();

        $this->assertSame(Status::Locked, $log->fresh()->status->enum);
    }

    #[Test]
    public function it_spares_terminal_logs(): void
    {
        $log = Log::factory()->mqtt()->failed()->createQuietly();
        $log->forceFill(['updated_at' => now()->subMinutes(config('event_log.watchdog.grace') + 1)])->saveQuietly();

        Log::watchdog()->bite()->now();

        $this->assertSame(Status::Failed, $log->fresh()->status->enum);
    }
}
