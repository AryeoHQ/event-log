<?php

declare(strict_types=1);

namespace Support\Events\Log\DeliveryAttempts\Status\Triggers;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;
use Support\Events\Log\DeliveryAttempts;
use Tests\TestCase;

#[CoversClass(Disqualify::class)]
final class DisqualifyTest extends TestCase
{
    #[Test]
    public function it_redispatches_to_the_queue_when_the_transition_fails(): void
    {
        $attempt = DeliveryAttempts\DeliveryAttempt::factory()->mqtt()->locked()->createQuietly();

        Queue::fake();
        Event::listen(DeliveryAttempts\Events\Updating::class, fn () => throw new RuntimeException);

        rescue(fn () => $attempt->status->disqualify()->dispatchAfterFailed()->now(), null, false);

        Queue::assertPushed(Disqualify::class);
    }
}
