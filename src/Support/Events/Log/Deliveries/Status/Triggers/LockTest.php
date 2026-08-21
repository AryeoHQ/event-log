<?php

declare(strict_types=1);

namespace Support\Events\Log\Deliveries\Status\Triggers;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;
use Support\Events\Log\Deliveries\Delivery;
use Support\Events\Log\Deliveries\Status\Events\Locking;
use Tests\TestCase;

#[CoversClass(Lock::class)]
final class LockTest extends TestCase
{
    #[Test]
    public function it_dispatches_process(): void
    {
        Queue::fake();

        Delivery::factory()->mqtt()->createQuietly()->status->lock()->now();

        Queue::assertPushed(Process::class);
    }

    #[Test]
    public function it_fires_fail_trigger_when_the_transition_fails(): void
    {
        Fail::fake();

        $delivery = Delivery::factory()->mqtt()->createQuietly();

        Event::listen(Locking::class, fn () => throw new RuntimeException);

        rescue(fn () => $delivery->status->lock()->now(), null, false);

        Fail::assertFired();
    }
}
