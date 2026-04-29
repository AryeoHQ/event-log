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

/** @extends RuleTestCase<HasLoggableMustImplementRecordable> */
#[CoversClass(HasLoggableMustImplementRecordable::class)]
final class HasLoggableMustImplementRecordableTest extends RuleTestCase
{
    use GetsFixtures;

    protected function getRule(): Rule
    {
        return new HasLoggableMustImplementRecordable;
    }

    #[Test]
    public function it_passes_when_has_loggable_implements_recordable(): void
    {
        $this->analyse([$this->getFixturePath('EventLog/ValidRecordableEvent.php')], []);
    }

    #[Test]
    public function it_passes_when_class_does_not_use_has_loggable(): void
    {
        $this->analyse([$this->getFixturePath('EventLog/ClassNotRecordable.php')], []);
    }

    #[Test]
    public function it_fails_when_has_loggable_does_not_implement_recordable(): void
    {
        $this->analyse([$this->getFixturePath('EventLog/HasLoggableWithoutRecordable.php')], [
            [
                class_basename(HasLoggable::class).' must implement the '.class_basename(Recordable::class).' interface.',
                9,
            ],
        ]);
    }
}
