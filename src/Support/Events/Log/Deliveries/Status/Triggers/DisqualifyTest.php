<?php

declare(strict_types=1);

namespace Support\Events\Log\Deliveries\Status\Triggers;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;
use Support\Events\Log\Deliveries;
use Tests\TestCase;

#[CoversClass(Disqualify::class)]
final class DisqualifyTest extends TestCase
{
    #[Test]
    public function it_redispatches_to_the_queue_when_the_transition_fails(): void
    {
        $delivery = Deliveries\Delivery::factory()->mqtt()->locked()->createQuietly();

        Queue::fake();
        Event::listen(Deliveries\Events\Updating::class, fn () => throw new RuntimeException);

        rescue(fn () => $delivery->status->disqualify()->dispatchAfterFailed()->now(), null, false);

        Queue::assertPushed(Disqualify::class);
    }
}
