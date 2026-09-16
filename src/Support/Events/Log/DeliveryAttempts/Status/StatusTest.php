<?php

declare(strict_types=1);

namespace Support\Events\Log\DeliveryAttempts\Status;

use Illuminate\Support\Facades\Event;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;
use Support\Events\Log\Deliveries\Delivery;
use Support\Events\Log\DeliveryAttempts\DeliveryAttempt;
use Support\Events\Log\DeliveryAttempts\Exceptions\Failed;
use Support\Events\Log\DeliveryAttempts\Exceptions\Undeliverable;
use Support\Events\Log\DeliveryAttempts\Status\Events\Locking;
use Tests\Fixtures\Support\Mqtt\Sending\Events\NeedsSent;
use Tests\Fixtures\Tooling\EventLog\RelayableWithoutDispatches;
use Tests\TestCase;

#[CoversClass(Status::class)]
final class StatusTest extends TestCase
{
    #[Test]
    public function pending_transitions(): void
    {
        Status::Pending->assertDefinesTransitions(Status::Locked, Status::Failed, Status::Undeliverable);
    }

    #[Test]
    public function locked_transitions(): void
    {
        Status::Locked->assertDefinesTransitions(Status::Succeeded, Status::Failed, Status::Undeliverable);
    }

    #[Test]
    public function failed_is_terminal(): void
    {
        Status::Failed->assertIsTerminal();
    }

    #[Test]
    public function succeeded_is_terminal(): void
    {
        Status::Succeeded->assertIsTerminal();
    }

    #[Test]
    public function undeliverable_is_terminal(): void
    {
        Status::Undeliverable->assertIsTerminal();
    }

    #[Test]
    public function it_succeeds_a_pending_attempt(): void
    {
        $attempt = DeliveryAttempt::factory()->mqtt()->createQuietly();

        $attempt->status->lock()->now();

        $this->assertSame(Status::Succeeded, $attempt->fresh()->status->enum);
    }

    #[Test]
    public function it_disqualifies_an_attempt_when_sending_finds_it_undeliverable(): void
    {
        Event::forget(NeedsSent::class);
        Event::listen(NeedsSent::class, fn () => throw new Undeliverable('recipient gone'));

        $attempt = DeliveryAttempt::factory()->mqtt()->createQuietly();

        rescue(fn () => $attempt->status->lock()->now(), null, false);

        $this->assertSame(Status::Undeliverable, $attempt->fresh()->status->enum);
    }

    #[Test]
    public function it_fails_an_attempt_when_sending_fails(): void
    {
        Event::forget(NeedsSent::class);
        Event::listen(NeedsSent::class, fn () => throw new Failed('recipient returned 500'));

        $delivery = Delivery::factory()->mqtt()->createQuietly();

        rescue(fn () => $delivery->attempts()->create(), null, false);

        $this->assertSame(Status::Failed, DeliveryAttempt::first()->status->enum);
    }

    #[Test]
    public function it_fails_an_attempt_when_locking_fails(): void
    {
        $attempt = DeliveryAttempt::factory()->mqtt()->createQuietly();

        Event::listen(Locking::class, fn () => throw new RuntimeException);

        rescue(fn () => $attempt->status->lock()->now(), null, false);

        $this->assertSame(Status::Failed, $attempt->fresh()->status->enum);
    }

    #[Test]
    public function it_disqualifies_an_attempt_when_locking_finds_it_undeliverable(): void
    {
        $attempt = DeliveryAttempt::factory()->mqtt()->createQuietly();

        Event::listen(Locking::class, fn () => throw new Undeliverable('recipient gone'));

        rescue(fn () => $attempt->status->lock()->now(), null, false);

        $this->assertSame(Status::Undeliverable, $attempt->fresh()->status->enum);
    }

    #[Test]
    public function it_fails_an_attempt_when_the_transport_has_no_dispatches(): void
    {
        $delivery = Delivery::factory()->mqtt(RelayableWithoutDispatches::class)->createQuietly();

        rescue(fn () => $delivery->attempts()->create(), null, false);

        $this->assertSame(Status::Failed, DeliveryAttempt::first()->status->enum);
    }
}
