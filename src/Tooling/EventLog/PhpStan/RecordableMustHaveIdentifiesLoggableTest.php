<?php

declare(strict_types=1);

namespace Tooling\EventLog\PhpStan;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Support\Events\Log\Contracts\Recordable;
use Support\Events\Log\IdentifiesLoggable\IdentifiesLoggable;
use Tests\Tooling\Concerns\GetsFixtures;

/** @extends RuleTestCase<RecordableMustHaveIdentifiesLoggable> */
#[CoversClass(RecordableMustHaveIdentifiesLoggable::class)]
final class RecordableMustHaveIdentifiesLoggableTest extends RuleTestCase
{
    use GetsFixtures;

    protected function getRule(): Rule
    {
        return new RecordableMustHaveIdentifiesLoggable;
    }

    #[Test]
    public function it_passes_when_recordable_has_identifies_loggable(): void
    {
        $this->analyse([$this->getFixturePath('EventLog/ValidRecordableEvent.php')], []);
    }

    #[Test]
    public function it_passes_when_class_does_not_implement_recordable(): void
    {
        $this->analyse([$this->getFixturePath('EventLog/ClassNotRecordable.php')], []);
    }

    #[Test]
    public function it_fails_when_recordable_is_missing_identifies_loggable(): void
    {
        $this->analyse([$this->getFixturePath('EventLog/HasLoggableWithoutIdentifiesLoggableAttribute.php')], [
            [
                class_basename(Recordable::class).' must have a property annotated with #['.class_basename(IdentifiesLoggable::class).'].',
                10,
            ],
        ]);
    }
}
