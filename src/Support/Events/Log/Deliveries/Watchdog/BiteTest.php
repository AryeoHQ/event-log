<?php

declare(strict_types=1);

namespace Support\Events\Log\Deliveries\Watchdog;

use Illuminate\Contracts\Queue\ShouldBeUnique;
use Orchestra\Testbench\Attributes\WithConfig;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Support\Events\Log\Deliveries\Delivery;
use Support\Events\Log\Deliveries\Status\Status;
use Tests\TestCase;

#[CoversClass(Bite::class)]
final class BiteTest extends TestCase
{
    #[Test]
    #[WithConfig('event_log.queues.delivery', 'deliveries')]
    public function it_runs_on_the_delivery_queue(): void
    {
        $this->assertSame('deliveries', Bite::make()->queue);
    }

    #[Test]
    public function it_is_unique_for_an_hour(): void
    {
        $this->assertContains(ShouldBeUnique::class, class_implements(Bite::class));
        $this->assertSame(3600, Bite::make()->uniqueFor);
    }

    #[Test]
    public function it_fails_deliveries_past_the_grace_period(): void
    {
        $delivery = Delivery::factory()->mqtt()->locked()->createQuietly();
        $delivery->forceFill(['updated_at' => now()->subMinutes(config('event_log.watchdog.grace') + 1)])->saveQuietly();

        Bite::make()->now();

        $this->assertSame(Status::Failed, $delivery->fresh()->status->enum);
    }

    #[Test]
    public function it_spares_deliveries_within_the_grace_period(): void
    {
        $delivery = Delivery::factory()->mqtt()->locked()->createQuietly();

        Bite::make()->now();

        $this->assertSame(Status::Locked, $delivery->fresh()->status->enum);
    }

    #[Test]
    public function it_spares_a_stale_delivery_once_an_attempt_touches_it(): void
    {
        $delivery = Delivery::factory()->mqtt()->locked()->createQuietly();
        $delivery->forceFill(['updated_at' => now()->subMinutes(config('event_log.watchdog.grace') + 1)])->saveQuietly();

        $delivery->attempts()->createQuietly();

        Bite::make()->now();

        $this->assertSame(Status::Locked, $delivery->fresh()->status->enum);
    }
}
