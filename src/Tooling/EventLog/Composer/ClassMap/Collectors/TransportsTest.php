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
use Tests\Fixtures\Tooling\EventLog\TransportWithoutHasRelays;
use Tests\TestCase;
use Tooling\Composer\ClassMap\Collectors\Provides\FakeableTestCases;
use Tooling\Composer\Composer;

#[CoversClass(Transports::class)]
final class TransportsTest extends TestCase
{
    use FakeableTestCases;

    #[Test]
    public function it_collects_only_concrete_classes_implementing_transport(): void
    {
        $reflections = resolve(Composer::class)->sourcePsr4ClassMap->keys()
            ->mapWithKeys(fn (string $class): array => [$class => rescue(fn (): ReflectionClass => new ReflectionClass($class), report: false)])
            ->filter();

        $collected = $reflections
            ->filter(fn (ReflectionClass $class): bool => new Transports()->collects($class, $reflections->all()))
            ->keys();

        $this->assertTrue($collected->contains(Updated::class));
        $this->assertTrue($collected->contains(Multiplexed::class));

        $this->assertFalse($collected->contains(Mqtt::class), 'transport interfaces');
        $this->assertFalse($collected->contains(Transport::class), 'the contract itself');
        $this->assertFalse($collected->contains(Relayable::class), 'classes without a transport');
        $this->assertFalse($collected->contains(TransportWithoutHasRelays::class), 'tooling fixtures');
    }
}
