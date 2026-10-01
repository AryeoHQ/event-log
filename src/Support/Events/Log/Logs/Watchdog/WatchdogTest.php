<?php

declare(strict_types=1);

namespace Support\Events\Log\Logs\Watchdog;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

#[CoversClass(Watchdog::class)]
final class WatchdogTest extends TestCase
{
    #[Test]
    public function it_bites(): void
    {
        $this->assertInstanceOf(Bite::class, (new Watchdog)->bite());
    }
}
