<?php

declare(strict_types=1);

namespace Support\Events\Log\Deliveries\Listeners;

use Illuminate\Support\Facades\Event;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Support\Events\Log\Deliveries\Events\Created;
use Support\Events\Log\Deliveries\Status\Triggers\Lock;
use Tests\Fixtures\Support\Entities\Relayable\Relayable;
use Tests\TestCase;

#[CoversClass(InitiateLifecycle::class)]
final class InitiateLifecycleTest extends TestCase
{
    #[Test]
    public function it_listens_for_creation(): void
    {
        Event::fake();

        Event::assertListening(Created::class, InitiateLifecycle::class);
    }

    #[Test]
    public function it_fires_lock(): void
    {
        Lock::fake();

        Relayable::factory()->create()->announceToLog();

        Lock::assertFired();
    }
}
