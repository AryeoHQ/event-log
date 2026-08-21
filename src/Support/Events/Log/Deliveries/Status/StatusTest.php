<?php

declare(strict_types=1);

namespace Support\Events\Log\Deliveries\Status;

use Illuminate\Queue\WorkerOptions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;
use Support\Events\Log\Deliveries\Delivery;
use Support\Events\Log\Deliveries\Status\Events\Locking;
use Support\Events\Log\DeliveryAttempts\DeliveryAttempt;
use Support\Events\Log\DeliveryAttempts\Exceptions\Failed;
use Support\Events\Log\DeliveryAttempts\Exceptions\Undeliverable;
use Support\Events\Log\DeliveryAttempts\Status\Status as AttemptStatus;
use Support\Events\Log\Envelopes\Envelope;
use Tests\Fixtures\Support\Entities\Recordable\Recordable;
use Tests\Fixtures\Support\Entities\Relayable\Relayable;
use Tests\Fixtures\Support\Mqtt\PayloadVersion;
use Tests\Fixtures\Support\Mqtt\Sending\Events\NeedsSent;
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
        Status::Locked->assertDefinesTransitions(
            Status::Locked,
            Status::Succeeded,
            Status::Failed,
            Status::Undeliverable,
        );
    }

    #[Test]
    public function failed_transitions(): void
    {
        Status::Failed->assertDefinesTransitions(Status::Pending);
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
    public function it_succeeds_a_pending_delivery(): void
    {
        $delivery = Delivery::factory()->mqtt()->createQuietly();

        $delivery->status->lock()->now();

        $this->assertSame(Status::Succeeded, $delivery->fresh()->status->enum);
    }

    #[Test]
    public function it_disqualifies_a_delivery_when_the_version_is_not_present(): void
    {
        $delivery = Delivery::factory()->mqtt()
            ->state(['envelope' => Envelope::make(recipient: Recordable::factory()->create(), version: PayloadVersion::V2)])
            ->createQuietly();

        $delivery->status->lock()->now();

        $this->assertSame(Status::Undeliverable, $delivery->fresh()->status->enum);
        $this->assertSame(0, DeliveryAttempt::count());
    }

    #[Test]
    public function it_disqualifies_a_delivery_when_the_recipient_has_been_deleted(): void
    {
        $recipient = Recordable::factory()->create();

        $delivery = Delivery::factory()->mqtt()
            ->state(['envelope' => Envelope::make(recipient: $recipient)])
            ->createQuietly();

        $recipient->delete();

        $delivery->status->lock()->now();

        $this->assertSame(Status::Undeliverable, $delivery->fresh()->status->enum);
        $this->assertSame(0, DeliveryAttempt::count());
    }

    #[Test]
    public function it_disqualifies_a_delivery_when_sending_finds_it_undeliverable(): void
    {
        Event::forget(NeedsSent::class);
        Event::listen(NeedsSent::class, fn () => throw new Undeliverable('recipient gone'));

        $delivery = Delivery::factory()->mqtt()->createQuietly();

        rescue(fn () => $delivery->status->lock()->now(), null, false);

        $this->assertSame(Status::Undeliverable, $delivery->fresh()->status->enum);
    }

    #[Test]
    public function it_disqualifies_an_undeliverable_delivery_when_redriven_directly(): void
    {
        $delivery = Delivery::factory()->mqtt()
            ->state(['envelope' => Envelope::make(recipient: Recordable::factory()->create(), version: PayloadVersion::V2)])
            ->locked()
            ->createQuietly();

        // Drive process DIRECTLY through ->now() to exercise $this->fail()
        // under a placeholder SyncJob (empty payload).
        $delivery->status->process()->now();

        $this->assertSame(Status::Undeliverable, $delivery->fresh()->status->enum);
    }

    #[Test]
    public function it_fails_a_delivery_when_locking_fails(): void
    {
        $delivery = Delivery::factory()->mqtt()->createQuietly();

        Event::listen(Locking::class, fn () => throw new RuntimeException);

        rescue(fn () => $delivery->status->lock()->now(), null, false);

        $this->assertSame(Status::Failed, $delivery->fresh()->status->enum);
    }

    #[Test]
    public function it_succeeds_a_failed_delivery_when_retried(): void
    {
        $delivery = Delivery::factory()->mqtt()->failed()->createQuietly();

        $delivery->status->retry()->now();

        $this->assertSame(Status::Succeeded, $delivery->fresh()->status->enum);
    }

    #[Test]
    public function it_records_one_attempt_row_per_try(): void
    {
        config()->set('queue.default', 'database');

        Event::forget(NeedsSent::class);
        Event::listen(NeedsSent::class, fn () => throw new Failed('recipient returned 500'));

        Relayable::factory()->create()->announceToLog();

        // Drain the cascade job by job (log process → relay process → delivery
        // process × tries), travelling past the backoff between runs.
        foreach (range(1, 10) as $run) {
            if (DB::table('jobs')->count() === 0) {
                break;
            }

            app('queue.worker')->runNextJob('database', 'default', new WorkerOptions);

            $this->travel(30)->seconds();
        }

        $delivery = Delivery::first();
        $this->assertSame(3, $delivery->tries);
        $this->assertSame(Status::Failed, $delivery->fresh()->status->enum);

        $attempts = DeliveryAttempt::all();
        $this->assertCount(3, $attempts);
        $attempts->each(function (DeliveryAttempt $attempt): void {
            $this->assertSame(AttemptStatus::Failed, $attempt->status->enum);
            $this->assertSame('recipient returned 500', $attempt->response);
        });
    }
}
