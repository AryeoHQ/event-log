<?php

declare(strict_types=1);

namespace Support\Events\Log\Logs\Status\Triggers;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;
use Support\Events\Log\Logs;
use Tests\TestCase;

#[CoversClass(Compromise::class)]
final class CompromiseTest extends TestCase
{
    #[Test]
    public function it_redispatches_to_the_queue_when_the_transition_fails(): void
    {
        $log = Logs\Log::factory()->mqtt()->locked()->createQuietly();

        Queue::fake();
        Event::listen(Logs\Events\Updating::class, fn () => throw new RuntimeException);

        rescue(fn () => $log->status->compromise()->dispatchAfterFailed()->now(), null, false);

        Queue::assertPushed(Compromise::class);
    }
}
