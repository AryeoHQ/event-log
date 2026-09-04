<?php

declare(strict_types=1);

namespace Support\Events\Log\DeliveryAttempts\Status\Triggers;

use Illuminate\Support\Facades\Event;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversTrait;
use PHPUnit\Framework\Attributes\Test;
use Support\Events\Log\Deliveries\Delivery;
use Support\Events\Log\DeliveryAttempts\DeliveryAttempt;
use Support\Events\Log\DeliveryAttempts\Exceptions\Failed;
use Support\Events\Log\DeliveryAttempts\Exceptions\Undeliverable;
use Support\Events\Log\Transports\Dispatches\Exceptions\NotDefined;
use Support\Events\Log\Transports\Dispatches\Sending\Provides\RecordsResult;
use Tests\Fixtures\Support\Entities\Relayable\Relayable;
use Tests\Fixtures\Support\Mqtt\Sending\Events\NeedsSent;
use Tests\Fixtures\Tooling\EventLog\RelayableWithoutDispatches;
use Tests\TestCase;

#[CoversClass(Process::class)]
#[CoversTrait(RecordsResult::class)]
final class ProcessTest extends TestCase
{
    #[Test]
    public function it_fires_sending_event(): void
    {
        Event::fake([NeedsSent::class]);

        Relayable::factory()->create()->announceToLog();

        Event::assertDispatched(NeedsSent::class);
    }

    #[Test]
    public function it_stores_the_listener_result_as_the_response(): void
    {
        Relayable::factory()->create()->announceToLog();

        $this->assertSame('published', DeliveryAttempt::first()->response);
    }

    #[Test]
    public function it_records_when_the_send_was_attempted(): void
    {
        Relayable::factory()->create()->announceToLog();

        $this->assertNotNull(DeliveryAttempt::first()->attempted_at);
    }

    #[Test]
    public function it_fires_disqualify_trigger_when_the_listener_throws_undeliverable(): void
    {
        Disqualify::fake();

        Event::forget(NeedsSent::class);
        Event::listen(NeedsSent::class, fn () => throw new Undeliverable('recipient gone'));

        $attempt = DeliveryAttempt::factory()->mqtt()->createQuietly();

        rescue(fn () => $attempt->status->lock()->now(), null, false);

        Disqualify::assertFired();

        $attempt = $attempt->fresh();
        $this->assertSame('recipient gone', $attempt->response);
        $this->assertNotNull($attempt->attempted_at);
    }

    #[Test]
    public function it_fires_fail_trigger_when_the_listener_throws_failed(): void
    {
        Fail::fake();

        Event::forget(NeedsSent::class);
        Event::listen(NeedsSent::class, fn () => throw new Failed('recipient returned 500'));

        $attempt = DeliveryAttempt::factory()->mqtt()->createQuietly();

        rescue(fn () => $attempt->status->lock()->now(), null, false);

        Fail::assertFired();
        $this->assertSame('recipient returned 500', $attempt->fresh()->response);
    }

    #[Test]
    public function it_fires_fail_trigger_when_the_transport_has_no_dispatches(): void
    {
        Fail::fake();

        $delivery = Delivery::factory()->mqtt(RelayableWithoutDispatches::class)->createQuietly();

        rescue(fn () => $delivery->attempts()->create(), null, false);

        Fail::assertFired();
        $this->assertSame((new NotDefined(RelayableWithoutDispatches::class))->getMessage(), DeliveryAttempt::first()->response);
    }
}
