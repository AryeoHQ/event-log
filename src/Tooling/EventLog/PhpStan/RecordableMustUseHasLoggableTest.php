<?php

declare(strict_types=1);

namespace Tooling\EventLog\PhpStan;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Support\Events\Log\Contracts\Recordable;
use Support\Events\Log\Provides\HasLoggable;
use Tests\Tooling\Concerns\GetsFixtures;

/** @extends RuleTestCase<RecordableMustUseHasLoggable> */
#[CoversClass(RecordableMustUseHasLoggable::class)]
final class RecordableMustUseHasLoggableTest extends RuleTestCase
{
    use GetsFixtures;

    protected function getRule(): Rule
    {
        return new RecordableMustUseHasLoggable;
    }

    #[Test]
    public function it_passes_when_recordable_uses_has_loggable_trait(): void
    {
        $this->analyse([$this->getFixturePath('EventLog/ValidRecordableEvent.php')], []);
    }

    #[Test]
    public function it_passes_when_class_does_not_implement_recordable(): void
    {
        $this->analyse([$this->getFixturePath('EventLog/ClassNotRecordable.php')], []);
    }

    #[Test]
    public function it_fails_when_recordable_does_not_use_has_loggable_trait(): void
    {
        $this->analyse([$this->getFixturePath('EventLog/RecordableWithoutHasLoggable.php')], [
            [
                class_basename(Recordable::class).' must use the '.class_basename(HasLoggable::class).' trait.',
                11,
            ],
        ]);
    }
}
