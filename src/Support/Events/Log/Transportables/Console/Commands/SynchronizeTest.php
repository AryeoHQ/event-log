<?php

declare(strict_types=1);

namespace Support\Events\Log\Transportables\Console\Commands;

use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Support\Events\Log\Transportables\Synchronize as SynchronizeTransportables;
use Support\Events\Log\Transportables\Transportable;
use Tests\TestCase;

#[CoversClass(Synchronize::class)]
final class SynchronizeTest extends TestCase
{
    #[Test]
    public function it_queues_the_synchronization_by_default(): void
    {
        Queue::fake();

        $this->artisan(Synchronize::class)->assertOk();

        Queue::assertPushed(SynchronizeTransportables::class);
    }

    #[Test]
    public function it_runs_synchronously_with_the_sync_option(): void
    {
        Queue::fake();

        $this->artisan(Synchronize::class, ['--sync' => true])->assertOk();

        Queue::assertNothingPushed();
        $this->assertNotEmpty(Transportable::all());
    }
}
