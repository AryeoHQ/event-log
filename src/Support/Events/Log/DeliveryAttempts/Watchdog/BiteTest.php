<?php

declare(strict_types=1);

namespace Support\Events\Log\DeliveryAttempts\Watchdog;

use Illuminate\Contracts\Queue\ShouldBeUnique;
use Orchestra\Testbench\Attributes\WithConfig;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Support\Events\Log\DeliveryAttempts\DeliveryAttempt;
use Support\Events\Log\DeliveryAttempts\Status\Status;
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
    public function it_fails_attempts_past_the_grace_period(): void
    {
        $attempt = DeliveryAttempt::factory()->mqtt()->locked()->createQuietly();
        $attempt->forceFill(['updated_at' => now()->subMinutes(config('event_log.watchdog.grace') + 1)])->saveQuietly();

        Bite::make()->now();

        $this->assertSame(Status::Failed, $attempt->fresh()->status->enum);
    }

    #[Test]
    public function it_spares_attempts_within_the_grace_period(): void
    {
        $attempt = DeliveryAttempt::factory()->mqtt()->locked()->createQuietly();

        Bite::make()->now();

        $this->assertSame(Status::Locked, $attempt->fresh()->status->enum);
    }
}
