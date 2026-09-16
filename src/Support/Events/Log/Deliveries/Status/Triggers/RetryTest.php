<?php

declare(strict_types=1);

namespace Support\Events\Log\Deliveries\Status\Triggers;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Support\Events\Log\Deliveries\Delivery;
use Tests\TestCase;

#[CoversClass(Retry::class)]
final class RetryTest extends TestCase
{
    #[Test]
    public function it_increments_tries_and_fires_lock_trigger(): void
    {
        Lock::fake();

        $delivery = Delivery::factory()->mqtt()->failed()->createQuietly();
        $tries = $delivery->tries;

        $delivery->status->retry()->now();

        $this->assertSame($tries + 1, $delivery->fresh()->tries);
        Lock::assertFired();
    }
}
