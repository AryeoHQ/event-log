<?php

declare(strict_types=1);

namespace Support\Events\Log\Transports\Dispatches\Sending\Provides;

use PHPUnit\Framework\Attributes\CoversTrait;
use PHPUnit\Framework\Attributes\Test;
use Support\Events\Log\Deliveries\Delivery;
use Tests\Fixtures\Support\Mqtt\Sending\Events\NeedsSent;
use Tests\TestCase;

#[CoversTrait(RecordsResult::class)]
final class RecordsResultTest extends TestCase
{
    #[Test]
    public function it_exposes_the_delivery_id_as_the_idempotency_key(): void
    {
        $delivery = Delivery::factory()->mqtt()->createQuietly();

        $event = new NeedsSent($delivery);

        $this->assertSame($delivery->id, $event->idempotencyKey);
    }
}
