<?php

declare(strict_types=1);

namespace Support\Events\Log\Logs\Status\Triggers;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Support\Events\Log\Logs\Log;
use Tests\TestCase;

#[CoversClass(Retry::class)]
final class RetryTest extends TestCase
{
    #[Test]
    public function it_fires_lock_trigger(): void
    {
        Lock::fake();

        $log = Log::factory()->mqtt()->failed()->createQuietly();

        $log->status->retry()->now();

        Lock::assertFired();
    }
}
