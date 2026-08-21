<?php

declare(strict_types=1);

namespace Support\Events\Log\Relays\Status\Triggers;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Orchestra\Testbench\Attributes\WithConfig;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;
use Support\Events\Log\Relays\Events\Updating;
use Support\Events\Log\Relays\Relay;
use Tests\TestCase;

#[CoversClass(Fail::class)]
final class FailTest extends TestCase
{
    #[Test]
    public function it_redispatches_to_the_queue_when_the_transition_fails(): void
    {
        $relay = Relay::factory()->mqtt()->locked()->createQuietly();

        Queue::fake();
        Event::listen(Updating::class, fn () => throw new RuntimeException);

        rescue(fn () => $relay->status->fail()->dispatchAfterFailed()->now(), null, false);

        Queue::assertPushed(Fail::class);
    }

    #[Test]
    #[WithConfig('event_log.queues.'.Relay::class, 'relays')]
    public function it_carries_the_layer_queue(): void
    {
        $relay = Relay::factory()->mqtt()->locked()->createQuietly();

        $this->assertSame('relays', $relay->status->fail()->queue);
    }
}
