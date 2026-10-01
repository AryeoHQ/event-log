<?php

declare(strict_types=1);

namespace Support\Events\Log\Logs\Watchdog;

use Illuminate\Contracts\Queue\ShouldBeUnique;
use Orchestra\Testbench\Attributes\WithConfig;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Support\Events\Log\Logs\Log;
use Support\Events\Log\Logs\Status\Status;
use Tests\TestCase;

#[CoversClass(Bite::class)]
final class BiteTest extends TestCase
{
    #[Test]
    #[WithConfig('event_log.queues.log', 'logs')]
    public function it_runs_on_the_log_queue(): void
    {
        $this->assertSame('logs', Bite::make()->queue);
    }

    #[Test]
    public function it_is_unique_for_an_hour(): void
    {
        $this->assertContains(ShouldBeUnique::class, class_implements(Bite::class));
        $this->assertSame(3600, Bite::make()->uniqueFor);
    }

    #[Test]
    public function it_fails_logs_past_the_grace_period(): void
    {
        $log = Log::factory()->mqtt()->locked()->createQuietly();
        $log->forceFill(['updated_at' => now()->subMinutes(config('event_log.watchdog.grace') + 1)])->saveQuietly();

        Bite::make()->now();

        $this->assertSame(Status::Failed, $log->fresh()->status->enum);
    }

    #[Test]
    public function it_spares_logs_within_the_grace_period(): void
    {
        $log = Log::factory()->mqtt()->locked()->createQuietly();

        Bite::make()->now();

        $this->assertSame(Status::Locked, $log->fresh()->status->enum);
    }

    #[Test]
    public function it_spares_terminal_logs(): void
    {
        $log = Log::factory()->mqtt()->failed()->createQuietly();
        $log->forceFill(['updated_at' => now()->subMinutes(config('event_log.watchdog.grace') + 1)])->saveQuietly();

        Bite::make()->now();

        $this->assertSame(Status::Failed, $log->fresh()->status->enum);
    }
}
