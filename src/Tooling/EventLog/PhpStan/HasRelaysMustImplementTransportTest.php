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

/** @extends RuleTestCase<HasRelaysMustImplementTransport> */
#[CoversClass(HasRelaysMustImplementTransport::class)]
final class HasRelaysMustImplementTransportTest extends RuleTestCase
{
    use GetsFixtures;

    protected function getRule(): Rule
    {
        return new HasRelaysMustImplementTransport;
    }

    #[Test]
    public function it_passes_when_has_relays_implements_transport(): void
    {
        $this->analyse([$this->getFixturePath('../Support/Entities/Relayable/Events/Updated.php')], []);
    }

    #[Test]
    public function it_passes_when_class_does_not_use_has_relays(): void
    {
        $this->analyse([$this->getFixturePath('EventLog/ClassNotRecordable.php')], []);
    }

    #[Test]
    public function it_fails_when_has_relays_does_not_implement_transport(): void
    {
        $this->analyse([$this->getFixturePath('EventLog/HasRelaysWithoutTransport.php')], [
            [
                class_basename(HasRelays::class).' must implement an interface that extends '.class_basename(Transport::class).'.',
                11,
            ],
        ]);
    }
}
