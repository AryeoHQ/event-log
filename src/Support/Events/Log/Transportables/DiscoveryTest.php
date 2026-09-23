<?php

declare(strict_types=1);

namespace Support\Events\Log\Transportables;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Tests\Fixtures\Support\Amqp\Amqp;
use Tests\Fixtures\Support\Entities\Relayable\Events\Multiplexed;
use Tests\Fixtures\Support\Entities\Relayable\Relayable;
use Tests\Fixtures\Support\Mqtt\Mqtt;
use Tests\TestCase;
use Tooling\Composer\ClassMap\Cache;
use Tooling\EventLog\Composer\ClassMap\Collectors\Transports;

#[CoversClass(Discovery::class)]
final class DiscoveryTest extends TestCase
{
    #[Test]
    public function it_finds_every_collected_class(): void
    {
        $this->assertEqualsCanonicalizing(
            resolve(Cache::class)->get(Transports::class),
            resolve(Discovery::class)->classes->all(),
        );
    }

    #[Test]
    public function it_describes_each_class_by_alias_class_and_transports(): void
    {
        $multiplexed = resolve(Discovery::class)->transportables
            ->firstWhere('class', Multiplexed::class);

        $this->assertNotNull($multiplexed);
        $this->assertSame((string) new Multiplexed(new Relayable)->alias, $multiplexed['alias']);
        $this->assertEqualsCanonicalizing([Amqp::class, Mqtt::class], $multiplexed['transports']);
    }
}
