<?php

declare(strict_types=1);

namespace Tooling\EventLog\PhpStan;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Support\Events\Log\Contracts\Recordable;
use Tests\Tooling\Concerns\GetsFixtures;

/** @extends RuleTestCase<RecordableMustNotOverrideSerialization> */
#[CoversClass(RecordableMustNotOverrideSerialization::class)]
final class RecordableMustNotOverrideSerializationTest extends RuleTestCase
{
    use GetsFixtures;

    protected function getRule(): Rule
    {
        return new RecordableMustNotOverrideSerialization;
    }

    #[Test]
    public function it_passes_when_recordable_does_not_override_serialization(): void
    {
        $this->analyse([$this->getFixturePath('EventLog/ValidRecordableEvent.php')], []);
    }

    #[Test]
    public function it_passes_when_class_does_not_implement_recordable(): void
    {
        $this->analyse([$this->getFixturePath('EventLog/ClassNotRecordable.php')], []);
    }

    #[Test]
    public function it_fails_when_recordable_overrides_serialization_methods(): void
    {
        $this->analyse([$this->getFixturePath('EventLog/RecordableWithSerializationOverride.php')], [
            [
                class_basename(Recordable::class).' must not override __serialize().',
                14,
            ],
            [
                class_basename(Recordable::class).' must not override __unserialize().',
                14,
            ],
        ]);
    }
}
