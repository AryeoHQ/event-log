<?php

declare(strict_types=1);

namespace Support\Events\Log\Deliveries\Watchdog\Console\Commands;

use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Support\Events\Log\Deliveries\Delivery;
use Support\Events\Log\Deliveries\Status\Status;
use Support\Events\Log\Deliveries\Watchdog\Bite;
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
        $delivery = Delivery::factory()->mqtt()->locked()->createQuietly();
        $delivery->forceFill(['updated_at' => now()->subMinutes(config('event_log.watchdog.grace') + 1)])->saveQuietly();

        $this->artisan(Watchdog::class, ['--sync' => true])->assertOk();

        $this->assertSame(Status::Failed, $delivery->fresh()->status->enum);
    }
}
