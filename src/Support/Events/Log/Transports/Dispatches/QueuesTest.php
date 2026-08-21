<?php

declare(strict_types=1);

namespace Support\Events\Log\Transports\Dispatches;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use ReflectionClass;
use Tests\Fixtures\Support\Mqtt\Queued;
use Tests\TestCase;

#[CoversClass(Queues::class)]
final class QueuesTest extends TestCase
{
    #[Test]
    public function it_resolves_collecting_and_sending_from_attribute(): void
    {
        $attributes = (new ReflectionClass(Queued::class))
            ->getAttributes(Queues::class);

        $this->assertCount(1, $attributes);

        $queues = $attributes[0]->newInstance();

        $this->assertSame('mqtt.queues.collecting', $queues->collecting);
        $this->assertSame('mqtt.queues.sending', $queues->sending);
    }
}
