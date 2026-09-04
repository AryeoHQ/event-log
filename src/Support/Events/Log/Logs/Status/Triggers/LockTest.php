<?php

declare(strict_types=1);

namespace Support\Events\Log\Logs\Status\Triggers;

use Illuminate\Support\Facades\Event;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;
use Support\Events\Log\Logs\Log;
use Support\Events\Log\Logs\Status\Events\Locking;
use Tests\Fixtures\Support\Entities\Recordable\Events\Updated;
use Tests\Fixtures\Support\Entities\Recordable\Recordable;
use Tests\TestCase;

#[CoversClass(Lock::class)]
final class LockTest extends TestCase
{
    #[Test]
    public function it_fires_process_trigger(): void
    {
        Process::fake();

        $log = Log::factory()
            ->state(['event' => new Updated(Recordable::factory()->create())])
            ->createQuietly();

        $log->status->lock()->now();

        Process::assertFiredTimes(1);
    }

    #[Test]
    public function it_fires_fail_trigger_when_the_transition_fails(): void
    {
        Fail::fake();

        $log = Log::factory()->mqtt()->createQuietly();

        Event::listen(Locking::class, fn () => throw new RuntimeException);

        rescue(fn () => $log->status->lock()->now(), null, false);

        Fail::assertFired();
    }
}
