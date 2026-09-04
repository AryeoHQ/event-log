<?php

declare(strict_types=1);

namespace Support\Events\Log\DeliveryAttempts\Status\Triggers;

use Illuminate\Support\Facades\Event;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;
use Support\Events\Log\DeliveryAttempts\DeliveryAttempt;
use Support\Events\Log\DeliveryAttempts\Exceptions\Undeliverable;
use Support\Events\Log\DeliveryAttempts\Status\Events\Locking;
use Tests\Fixtures\Support\Entities\Relayable\Relayable;
use Tests\TestCase;

#[CoversClass(Lock::class)]
final class LockTest extends TestCase
{
    #[Test]
    public function it_processes_the_attempt(): void
    {
        Process::fake();

        Relayable::factory()->create()->announceToLog();

        Process::assertFired();
    }

    #[Test]
    public function it_fires_fail_trigger_when_the_transition_fails(): void
    {
        Fail::fake();

        $attempt = DeliveryAttempt::factory()->mqtt()->createQuietly();

        Event::listen(Locking::class, fn () => throw new RuntimeException('lock failed'));

        rescue(fn () => $attempt->status->lock()->now(), null, false);

        Fail::assertFired();
        $this->assertSame('lock failed', $attempt->fresh()->response);
    }

    #[Test]
    public function it_fires_disqualify_trigger_when_the_transition_fails_as_undeliverable(): void
    {
        Disqualify::fake();

        $attempt = DeliveryAttempt::factory()->mqtt()->createQuietly();

        Event::listen(Locking::class, fn () => throw new Undeliverable('recipient gone'));

        rescue(fn () => $attempt->status->lock()->now(), null, false);

        Disqualify::assertFired();
        $this->assertSame('recipient gone', $attempt->fresh()->response);
    }
}
