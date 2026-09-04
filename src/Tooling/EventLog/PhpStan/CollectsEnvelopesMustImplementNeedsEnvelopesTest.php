<?php

declare(strict_types=1);

namespace Tooling\EventLog\PhpStan;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Support\Events\Log\Transports\Dispatches\Collecting\Contracts\NeedsEnvelopes;
use Support\Events\Log\Transports\Dispatches\Collecting\Provides\CollectsEnvelopes;
use Tests\Tooling\Concerns\GetsFixtures;

/** @extends RuleTestCase<CollectsEnvelopesMustImplementNeedsEnvelopes> */
#[CoversClass(CollectsEnvelopesMustImplementNeedsEnvelopes::class)]
final class CollectsEnvelopesMustImplementNeedsEnvelopesTest extends RuleTestCase
{
    use GetsFixtures;

    protected function getRule(): Rule
    {
        return new CollectsEnvelopesMustImplementNeedsEnvelopes;
    }

    #[Test]
    public function it_passes_when_collects_envelopes_implements_needs_envelopes(): void
    {
        $this->analyse([$this->getFixturePath('../Support/Mqtt/Collecting/Events/NeedsEnvelopes.php')], []);
    }

    #[Test]
    public function it_passes_when_class_does_not_use_collects_envelopes(): void
    {
        $this->analyse([$this->getFixturePath('EventLog/ClassNotRecordable.php')], []);
    }

    #[Test]
    public function it_fails_when_collects_envelopes_does_not_implement_needs_envelopes(): void
    {
        $this->analyse([$this->getFixturePath('EventLog/CollectsEnvelopesWithoutNeedsEnvelopes.php')], [
            [
                class_basename(CollectsEnvelopes::class).' must implement the '.class_basename(NeedsEnvelopes::class).' interface.',
                10,
            ],
        ]);
    }
}
