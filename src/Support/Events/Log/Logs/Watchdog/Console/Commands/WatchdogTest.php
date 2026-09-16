<?php

declare(strict_types=1);

namespace Support\Events\Log\Logs\Watchdog\Console\Commands;

use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Support\Events\Log\Logs\Log;
use Support\Events\Log\Logs\Status\Status;
use Support\Events\Log\Logs\Watchdog\Bite;
use Tests\TestCase;

#[CoversClass(Watchdog::class)]
final class WatchdogTest extends TestCase
{
    #[Test]
    public function it_dispatches_the_bite_by_default(): void
    {
        Queue::fake();

        $this->artisan(Watchdog::class)->assertOk();

        Queue::assertPushed(Bite::class);
    }

    #[Test]
    public function it_runs_synchronously_with_the_sync_option(): void
    {
        $log = Log::factory()->mqtt()->locked()->createQuietly();
        $log->forceFill(['updated_at' => now()->subMinutes(config('event_log.watchdog.grace') + 1)])->saveQuietly();

        $this->artisan(Watchdog::class, ['--sync' => true])->assertOk();

        $this->assertSame(Status::Failed, $log->fresh()->status->enum);
    }
}
