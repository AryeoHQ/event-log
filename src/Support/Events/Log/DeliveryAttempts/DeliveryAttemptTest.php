<?php

declare(strict_types=1);

namespace Support\Events\Log\DeliveryAttempts;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Support\Events\Database\Eloquent\Swappable\Swapper\Facades\Swapper;
use Tests\TestCase;

#[CoversClass(DeliveryAttempt::class)]
final class DeliveryAttemptTest extends TestCase
{
    #[Test]
    public function it_supports_swapping(): void
    {
        $this->assertTrue(Swapper::isSwappable(DeliveryAttempt::class));
    }
}
