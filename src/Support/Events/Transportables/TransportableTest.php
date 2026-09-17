<?php

declare(strict_types=1);

namespace Support\Events\Transportables;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Tests\Fixtures\Support\Amqp\Amqp;
use Tests\Fixtures\Support\Entities\Relayable\Events\Multiplexed;
use Tests\Fixtures\Support\Entities\Relayable\Events\Updated;
use Tests\Fixtures\Support\Entities\Relayable\Relayable;
use Tests\Fixtures\Support\Mqtt\Mqtt;
use Tests\Fixtures\Support\Mqtt\Queued;
use Tests\TestCase;
use Tooling\Composer\ClassMap\Cache;
use Tooling\EventLog\Composer\ClassMap\Collectors\Transports;

#[CoversClass(Transportable::class)]
final class TransportableTest extends TestCase
{
    #[Test]
    public function it_exposes_a_row_per_transport(): void
    {
        $this->assertEqualsCanonicalizing(
            resolve(Cache::class)->get(Transports::class),
            Transportable::pluck('class')->all(),
        );
    }

    #[Test]
    public function it_keys_rows_by_alias(): void
    {
        $transportable = Transportable::find((string) new Updated(new Relayable)->alias);

        $this->assertNotNull($transportable);
        $this->assertSame(Updated::class, $transportable->class);
    }

    #[Test]
    public function it_records_every_transport_the_class_implements(): void
    {
        $transports = Transportable::find((string) new Multiplexed(new Relayable)->alias)?->transports;

        $this->assertNotNull($transports);
        $this->assertEqualsCanonicalizing([Amqp::class, Mqtt::class], $transports->all());
    }

    #[Test]
    public function it_queries_rows_by_transport(): void
    {
        $this->assertSame(
            [Multiplexed::class],
            Transportable::whereJsonContains('transports', Amqp::class)->pluck('class')->all(),
        );
    }

    #[Test]
    public function it_returns_no_rows_for_a_transport_no_event_implements(): void
    {
        $this->assertSame(
            0, Transportable::whereJsonContains('transports', Queued::class)->count()
        );
    }
}
