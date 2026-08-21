<?php

declare(strict_types=1);

namespace Support\Events\Log\Relays\Status\Triggers;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;
use Support\Events\Log\Relays\Relay;
use Support\Events\Log\Relays\Status\Events\Locking;
use Tests\TestCase;

#[CoversClass(Lock::class)]
final class LockTest extends TestCase
{
    #[Test]
    public function it_dispatches_process(): void
    {
        Queue::fake();

        Relay::factory()->mqtt()->createQuietly()->status->lock()->now();

        Queue::assertPushed(Process::class);
    }

    #[Test]
    public function it_fires_fail_trigger_when_the_transition_fails(): void
    {
        Fail::fake();

        $relay = Relay::factory()->mqtt()->createQuietly();

        Event::listen(Locking::class, fn () => throw new RuntimeException);

        rescue(fn () => $relay->status->lock()->now(), null, false);

        Fail::assertFired();
    }
}
