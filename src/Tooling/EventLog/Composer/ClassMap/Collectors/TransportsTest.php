<?php

declare(strict_types=1);

namespace Tooling\EventLog\Composer\ClassMap\Collectors;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use ReflectionClass;
use Support\Events\Log\Transports\Contracts\Transport;
use Tests\Fixtures\Support\Entities\Relayable\Events\Multiplexed;
use Tests\Fixtures\Support\Entities\Relayable\Events\Updated;
use Tests\Fixtures\Support\Entities\Relayable\Relayable;
use Tests\Fixtures\Support\Mqtt\Mqtt;
use Tests\Fixtures\Tooling\EventLog\TransportWithDuplicateAlias;
use Tests\TestCase;
use Tooling\Composer\ClassMap\Collectors\Provides\FakeableTestCases;

#[CoversClass(Transports::class)]
final class TransportsTest extends TestCase
{
    use FakeableTestCases;

    #[Test]
    public function it_collects_only_concrete_classes_implementing_transport(): void
    {
        $this->assertTrue($this->collector->collects(new ReflectionClass(Updated::class), []));
        $this->assertTrue($this->collector->collects(new ReflectionClass(Multiplexed::class), []));

        $this->assertFalse($this->collector->collects(new ReflectionClass(Mqtt::class), []), 'transport interfaces');
        $this->assertFalse($this->collector->collects(new ReflectionClass(Transport::class), []), 'the contract itself');
        $this->assertFalse($this->collector->collects(new ReflectionClass(Relayable::class), []), 'classes without a transport');
        $this->assertFalse($this->collector->collects(new ReflectionClass(TransportWithDuplicateAlias::class), []), 'tooling fixtures');
    }
}
