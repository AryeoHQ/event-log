<?php

declare(strict_types=1);

namespace Support\Events\Log\Transports\Dispatches;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use ReflectionClass;
use Support\Events\Log\Transports\Dispatches;
use Tests\Fixtures\Support\Mqtt\Collecting\Events\NeedsEnvelopes;
use Tests\Fixtures\Support\Mqtt\Mqtt;
use Tests\Fixtures\Support\Mqtt\Sending\Events\NeedsSent;
use Tests\Fixtures\Tooling\EventLog\RelayableWithoutDispatches;
use Tests\TestCase;

#[CoversClass(Dispatches\Dispatches::class)]
final class DispatchesTest extends TestCase
{
    #[Test]
    public function it_resolves_collecting_and_sending_from_attribute(): void
    {
        $attributes = (new ReflectionClass(Mqtt::class))
            ->getAttributes(Dispatches\Dispatches::class);

        $this->assertCount(1, $attributes);

        $dispatches = $attributes[0]->newInstance();

        $this->assertSame(NeedsEnvelopes::class, $dispatches->collecting);
        $this->assertSame(NeedsSent::class, $dispatches->sending);
    }

    #[Test]
    public function it_resolves_itself_from_a_transport(): void
    {
        $dispatches = Dispatches\Dispatches::on(Mqtt::class);

        $this->assertSame(NeedsEnvelopes::class, $dispatches->collecting);
        $this->assertSame(NeedsSent::class, $dispatches->sending);
    }

    #[Test]
    public function it_throws_when_the_transport_has_no_dispatches(): void
    {
        $this->expectException(Dispatches\Exceptions\NotDefined::class);

        Dispatches\Dispatches::on(RelayableWithoutDispatches::class);
    }

    #[Test]
    public function it_throws_when_collecting_does_not_implement_needs_envelopes(): void
    {
        $this->expectException(Dispatches\Collecting\Exceptions\Invalid::class);

        new Dispatches\Dispatches(collecting: self::class, sending: NeedsSent::class); // @phpstan-ignore argument.type
    }

    #[Test]
    public function it_throws_when_sending_does_not_implement_needs_sent(): void
    {
        $this->expectException(Dispatches\Sending\Exceptions\Invalid::class);

        new Dispatches\Dispatches(collecting: NeedsEnvelopes::class, sending: self::class); // @phpstan-ignore argument.type
    }
}
