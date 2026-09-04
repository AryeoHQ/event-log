<?php

declare(strict_types=1);

namespace Support\Events\Log\Relays\Status;

use Illuminate\Support\Facades\Event;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;
use Support\Events\Log\Deliveries\Delivery;
use Support\Events\Log\Relays\Relay;
use Support\Events\Log\Relays\Status\Events\Locking;
use Tests\Fixtures\Support\Mqtt\Collecting\Events\NeedsEnvelopes;
use Tests\TestCase;

#[CoversClass(Status::class)]
final class StatusTest extends TestCase
{
    #[Test]
    public function pending_transitions(): void
    {
        Status::Pending->assertDefinesTransitions(Status::Locked, Status::Failed);
    }

    #[Test]
    public function locked_transitions(): void
    {
        Status::Locked->assertDefinesTransitions(Status::Processed, Status::Failed);
    }

    #[Test]
    public function failed_transitions(): void
    {
        Status::Failed->assertDefinesTransitions(Status::Pending);
    }

    #[Test]
    public function processed_is_terminal(): void
    {
        Status::Processed->assertIsTerminal();
    }

    #[Test]
    public function it_processes_a_pending_relay(): void
    {
        $relay = Relay::factory()->mqtt()->createQuietly();

        $relay->status->lock()->now();

        $this->assertSame(Status::Processed, $relay->fresh()->status->enum);
        $this->assertCount(1, Delivery::all());
    }

    #[Test]
    public function it_processes_a_relay_when_no_envelopes_are_gathered(): void
    {
        Event::forget(NeedsEnvelopes::class);

        $relay = Relay::factory()->mqtt()->createQuietly();

        $relay->status->lock()->now();

        $this->assertSame(Status::Processed, $relay->fresh()->status->enum);
        $this->assertCount(0, Delivery::all());
    }

    #[Test]
    public function it_fails_a_relay_when_locking_fails(): void
    {
        $relay = Relay::factory()->mqtt()->createQuietly();

        Event::listen(Locking::class, fn () => throw new RuntimeException);

        rescue(fn () => $relay->status->lock()->now(), null, false);

        $this->assertSame(Status::Failed, $relay->fresh()->status->enum);
    }

    #[Test]
    public function it_fails_a_relay_when_gathering_fails(): void
    {
        Event::forget(NeedsEnvelopes::class);
        Event::listen(NeedsEnvelopes::class, fn () => throw new RuntimeException);

        $relay = Relay::factory()->mqtt()->createQuietly();

        rescue(fn () => $relay->status->lock()->now(), null, false);

        $this->assertSame(Status::Failed, $relay->fresh()->status->enum);
    }

    #[Test]
    public function it_processes_a_failed_relay_when_retried(): void
    {
        $relay = Relay::factory()->mqtt()->failed()->createQuietly();

        $relay->status->retry()->now();

        $this->assertSame(Status::Processed, $relay->fresh()->status->enum);
    }
}
