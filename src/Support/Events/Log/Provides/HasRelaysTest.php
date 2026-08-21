<?php

declare(strict_types=1);

namespace Support\Events\Log\Provides;

use PHPUnit\Framework\Attributes\CoversTrait;
use PHPUnit\Framework\Attributes\Test;
use Tests\Fixtures\Support\Entities\Relayable\Events\Updated;
use Tests\Fixtures\Support\Entities\Relayable\Relayable;
use Tests\Fixtures\Support\Mqtt\Mqtt;
use Tests\TestCase;

#[CoversTrait(HasRelays::class)]
final class HasRelaysTest extends TestCase
{
    #[Test]
    public function it_discovers_relay_interfaces(): void
    {
        $event = new Updated(
            Relayable::factory()->make(),
        );

        $relays = $event->transports;

        $this->assertCount(1, $relays);
        $this->assertTrue($relays->contains(Mqtt::class));
    }

    #[Test]
    public function it_excludes_non_relayable_interfaces(): void
    {
        $event = new Updated(
            Relayable::factory()->make(),
        );

        $relays = $event->transports;

        $this->assertFalse($relays->contains(\Support\Events\Log\Contracts\Recordable::class));
    }
}
