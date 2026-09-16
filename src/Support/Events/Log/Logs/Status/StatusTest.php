<?php

declare(strict_types=1);

namespace Support\Events\Log\Logs\Status;

use Illuminate\Support\Facades\Event;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;
use Support\Events\Log\Logs\Log;
use Support\Events\Log\Logs\Status\Events\Locking;
use Support\Events\Log\Relays\Relay;
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
        Status::Locked->assertDefinesTransitions(Status::Processed, Status::Failed, Status::Compromised);
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
    public function compromised_is_terminal(): void
    {
        Status::Compromised->assertIsTerminal();
    }

    #[Test]
    public function it_processes_a_pending_log(): void
    {
        $log = Log::factory()->mqtt()->createQuietly();

        $log->status->lock()->now();

        $this->assertSame(Status::Processed, $log->fresh()->status->enum);
    }

    #[Test]
    public function it_fails_a_log_when_locking_fails(): void
    {
        $log = Log::factory()->mqtt()->createQuietly();

        Event::listen(Locking::class, fn () => throw new RuntimeException);

        rescue(fn () => $log->status->lock()->now(), null, false);

        $this->assertSame(Status::Failed, $log->fresh()->status->enum);
    }

    #[Test]
    public function it_fails_a_log_when_processing_fails(): void
    {
        Relay::creating(fn (): bool => throw new RuntimeException);

        $log = Log::factory()->mqtt()->locked()->createQuietly();

        rescue(fn () => $log->status->process()->now(), null, false);

        $this->assertSame(Status::Failed, $log->fresh()->status->enum);
    }

    #[Test]
    public function it_compromises_a_log_when_the_event_is_tampered(): void
    {
        $log = Log::factory()->mqtt()->locked()->createQuietly();

        Log::withoutEvents(fn () => $log->newQuery()->toBase()->where('id', $log->id)->update([
            'event' => with(
                $log->getRawOriginal('event'),
                fn (string $raw) => substr($raw, 0, 64).'TAMPERED'.substr($raw, 72)
            ),
        ]));

        $log = $log->fresh();
        rescue(fn () => $log->status->process()->now(), null, false);

        $this->assertSame(Status::Compromised, $log->fresh()->status->enum);
    }

    #[Test]
    public function it_compromises_a_log_when_the_event_is_corrupted(): void
    {
        $log = Log::factory()->mqtt()->locked()->createQuietly();

        Log::withoutEvents(fn () => $log->newQuery()->toBase()->where('id', $log->id)->update([
            'event' => '!!!not-base64!!!',
        ]));

        $log = $log->fresh();
        rescue(fn () => $log->status->process()->now(), null, false);

        $this->assertSame(Status::Compromised, $log->fresh()->status->enum);
    }

    #[Test]
    public function it_processes_a_failed_log_when_retried(): void
    {
        $log = Log::factory()->mqtt()->failed()->createQuietly();

        $log->status->retry()->now();

        $this->assertSame(Status::Processed, $log->fresh()->status->enum);
    }
}
