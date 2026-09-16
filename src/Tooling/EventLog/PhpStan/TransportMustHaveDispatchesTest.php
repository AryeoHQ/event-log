<?php

declare(strict_types=1);

namespace Tooling\EventLog\PhpStan;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Support\Events\Log\Transports\Contracts\Transport;
use Support\Events\Log\Transports\Dispatches\Dispatches;
use Tests\Tooling\Concerns\GetsFixtures;

/** @extends RuleTestCase<TransportMustHaveDispatches> */
#[CoversClass(TransportMustHaveDispatches::class)]
final class TransportMustHaveDispatchesTest extends RuleTestCase
{
    use GetsFixtures;

    protected function getRule(): Rule
    {
        return new TransportMustHaveDispatches;
    }

    #[Test]
    public function it_passes_when_relayable_has_dispatches_attribute(): void
    {
        $this->analyse([$this->getFixturePath('../Support/Mqtt/Mqtt.php')], []);
    }

    #[Test]
    public function it_passes_when_interface_does_not_extend_relayable(): void
    {
        $this->analyse([$this->getFixturePath('EventLog/ClassNotRecordable.php')], []);
    }

    #[Test]
    public function it_fails_when_relayable_is_missing_dispatches_attribute(): void
    {
        $this->analyse([$this->getFixturePath('EventLog/RelayableWithoutDispatches.php')], [
            [
                class_basename(Transport::class).' must have a #['.class_basename(Dispatches::class).'] attribute.',
                9,
            ],
        ]);
    }
}
