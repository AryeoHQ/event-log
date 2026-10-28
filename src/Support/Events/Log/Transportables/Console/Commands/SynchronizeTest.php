<?php

declare(strict_types=1);

namespace Support\Events\Log\Transportables\Console\Commands;

use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Support\Events\Log\Transportables\Synchronize as SynchronizeTransportables;
use Support\Events\Log\Transportables\Transportable;
use Tests\Fixtures\Support\Entities\Relayable\Events\Updated;
use Tests\Fixtures\Support\Mqtt\Mqtt;
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

    #[Test]
    public function it_does_not_prune_by_default(): void
    {
        Queue::fake();

        $this->artisan(Synchronize::class)->assertOk();

        Queue::assertPushed(SynchronizeTransportables::class, fn (SynchronizeTransportables $job): bool => ! $job->shouldPrune);
    }

    #[Test]
    public function it_queues_a_pruning_synchronization_with_the_prune_option(): void
    {
        Queue::fake();

        $this->artisan(Synchronize::class, ['--prune' => true])->assertOk();

        Queue::assertPushed(SynchronizeTransportables::class, fn (SynchronizeTransportables $job): bool => $job->shouldPrune);
    }

    #[Test]
    public function it_prunes_synchronously_with_the_sync_and_prune_options(): void
    {
        Transportable::create([
            'id' => 'relayable.retired',
            'class' => Updated::class,
            'transports' => [Mqtt::class],
        ]);

        $this->artisan(Synchronize::class, ['--sync' => true, '--prune' => true])->assertOk();

        $this->assertNull(Transportable::find('relayable.retired'));
    }
}
