<?php

declare(strict_types=1);

namespace Tooling\EventLog\PhpStan;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Support\Events\Log\Transports\Dispatches\Collecting\Contracts\NeedsEnvelopes;
use Tests\Fixtures\Tooling\EventLog\CollectsEnvelopesWithoutNeedsEnvelopes;
use Tests\Tooling\Concerns\GetsFixtures;

/** @extends RuleTestCase<DispatchesCollectingMustImplementNeedsEnvelopes> */
#[CoversClass(DispatchesCollectingMustImplementNeedsEnvelopes::class)]
final class DispatchesCollectingMustImplementNeedsEnvelopesTest extends RuleTestCase
{
    use GetsFixtures;

    protected function getRule(): Rule
    {
        return new DispatchesCollectingMustImplementNeedsEnvelopes;
    }

    #[Test]
    public function it_passes_when_collecting_implements_needs_envelopes(): void
    {
        $this->analyse([$this->getFixturePath('../Support/Mqtt/Mqtt.php')], []);
    }

    #[Test]
    public function it_fails_when_collecting_does_not_implement_needs_envelopes(): void
    {
        $this->analyse([$this->getFixturePath('EventLog/RelayableWithInvalidDispatches.php')], [
            [
                'Collecting event ['.class_basename(CollectsEnvelopesWithoutNeedsEnvelopes::class).'] must implement '.class_basename(NeedsEnvelopes::class).'.',
                11,
            ],
        ]);
    }
}
