<?php

declare(strict_types=1);

namespace Tooling\EventLog\PhpStan;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Support\Events\Log\Transports\Dispatches\Sending\Contracts\NeedsSent;
use Support\Events\Log\Transports\Dispatches\Sending\Provides\RecordsResult;
use Tests\Tooling\Concerns\GetsFixtures;

/** @extends RuleTestCase<RecordsResultMustImplementNeedsSent> */
#[CoversClass(RecordsResultMustImplementNeedsSent::class)]
final class RecordsResultMustImplementNeedsSentTest extends RuleTestCase
{
    use GetsFixtures;

    protected function getRule(): Rule
    {
        return new RecordsResultMustImplementNeedsSent;
    }

    #[Test]
    public function it_passes_when_records_result_implements_needs_sent(): void
    {
        $this->analyse([$this->getFixturePath('../Support/Mqtt/Sending/Events/NeedsSent.php')], []);
    }

    #[Test]
    public function it_passes_when_class_does_not_use_records_result(): void
    {
        $this->analyse([$this->getFixturePath('EventLog/ClassNotRecordable.php')], []);
    }

    #[Test]
    public function it_fails_when_records_result_does_not_implement_needs_sent(): void
    {
        $this->analyse([$this->getFixturePath('EventLog/RecordsResultWithoutNeedsSent.php')], [
            [
                class_basename(RecordsResult::class).' must implement the '.class_basename(NeedsSent::class).' interface.',
                10,
            ],
        ]);
    }
}
