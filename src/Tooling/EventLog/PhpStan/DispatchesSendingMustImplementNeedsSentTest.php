<?php

declare(strict_types=1);

namespace Tooling\EventLog\PhpStan;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Support\Events\Log\Transports\Dispatches\Sending\Contracts\NeedsSent;
use Tests\Fixtures\Tooling\EventLog\RecordsResultWithoutNeedsSent;
use Tests\Tooling\Concerns\GetsFixtures;

/** @extends RuleTestCase<DispatchesSendingMustImplementNeedsSent> */
#[CoversClass(DispatchesSendingMustImplementNeedsSent::class)]
final class DispatchesSendingMustImplementNeedsSentTest extends RuleTestCase
{
    use GetsFixtures;

    protected function getRule(): Rule
    {
        return new DispatchesSendingMustImplementNeedsSent;
    }

    #[Test]
    public function it_passes_when_sending_implements_needs_sent(): void
    {
        $this->analyse([$this->getFixturePath('../Support/Mqtt/Mqtt.php')], []);
    }

    #[Test]
    public function it_fails_when_sending_does_not_implement_needs_sent(): void
    {
        $this->analyse([$this->getFixturePath('EventLog/RelayableWithInvalidDispatches.php')], [
            [
                'Sending event ['.class_basename(RecordsResultWithoutNeedsSent::class).'] must implement '.class_basename(NeedsSent::class).'.',
                11,
            ],
        ]);
    }
}
