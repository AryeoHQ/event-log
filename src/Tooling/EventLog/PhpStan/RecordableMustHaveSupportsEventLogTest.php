<?php

declare(strict_types=1);

namespace Tooling\EventLog\PhpStan;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Tests\Tooling\Concerns\GetsFixtures;

/** @extends RuleTestCase<RecordableMustHaveSupportsEventLog> */
#[CoversClass(RecordableMustHaveSupportsEventLog::class)]
class RecordableMustHaveSupportsEventLogTest extends RuleTestCase
{
    use GetsFixtures;

    protected function getRule(): Rule
    {
        return new RecordableMustHaveSupportsEventLog;
    }

    #[Test]
    public function it_passes_when_recordable_uses_supports_event_log(): void
    {
        $this->analyse([$this->getFixturePath('EventLog/ValidRecordableEvent.php')], []);
    }

    #[Test]
    public function it_passes_when_class_does_not_implement_recordable(): void
    {
        $this->analyse([$this->getFixturePath('EventLog/ClassWithoutSupportsEventLog.php')], []);
    }

    #[Test]
    public function it_fails_when_recordable_does_not_use_supports_event_log(): void
    {
        $this->analyse([$this->getFixturePath('EventLog/RecordableWithoutSupportsEventLog.php')], [
            [
                'Recordable must use SupportsEventLog.',
                11,
            ],
        ]);
    }
}
