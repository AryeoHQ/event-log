<?php

declare(strict_types=1);

namespace Tooling\EventLog\PhpStan;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Support\Events\Log\Provides\HasRelays;
use Support\Events\Log\Transports\Contracts\Transport;
use Tests\Tooling\Concerns\GetsFixtures;

/** @extends RuleTestCase<TransportMustUseHasRelays> */
#[CoversClass(TransportMustUseHasRelays::class)]
final class TransportMustUseHasRelaysTest extends RuleTestCase
{
    use GetsFixtures;

    protected function getRule(): Rule
    {
        return new TransportMustUseHasRelays;
    }

    #[Test]
    public function it_passes_when_transport_uses_has_relays(): void
    {
        $this->analyse([$this->getFixturePath('../Support/Entities/Relayable/Events/Updated.php')], []);
    }

    #[Test]
    public function it_passes_when_class_does_not_implement_transport(): void
    {
        $this->analyse([$this->getFixturePath('EventLog/ClassNotRecordable.php')], []);
    }

    #[Test]
    public function it_fails_when_transport_does_not_use_has_relays(): void
    {
        $this->analyse([$this->getFixturePath('EventLog/TransportWithoutHasRelays.php')], [
            [
                'A class that implements an interface extending '.class_basename(Transport::class).' must use the '.class_basename(HasRelays::class).' trait.',
                12,
            ],
        ]);
    }
}
