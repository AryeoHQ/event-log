<?php

declare(strict_types=1);

namespace Support\Events\Log\Relays\Status\Triggers;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Support\Events\Log\Relays\Relay;
use Tests\TestCase;

#[CoversClass(Retry::class)]
final class RetryTest extends TestCase
{
    #[Test]
    public function it_fires_lock_trigger(): void
    {
        Lock::fake();

        $relay = Relay::factory()->mqtt()->failed()->createQuietly();

        $relay->status->retry()->now();

        Lock::assertFired();
    }
}
