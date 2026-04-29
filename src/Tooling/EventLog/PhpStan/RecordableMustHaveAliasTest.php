<?php

declare(strict_types=1);

namespace Tooling\EventLog\PhpStan;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Support\Events\Log\Alias\Alias;
use Support\Events\Log\Contracts\Recordable;
use Tests\Tooling\Concerns\GetsFixtures;

/** @extends RuleTestCase<RecordableMustHaveAlias> */
#[CoversClass(RecordableMustHaveAlias::class)]
final class RecordableMustHaveAliasTest extends RuleTestCase
{
    use GetsFixtures;

    protected function getRule(): Rule
    {
        return new RecordableMustHaveAlias;
    }

    #[Test]
    public function it_passes_when_recordable_has_alias_attribute(): void
    {
        $this->analyse([$this->getFixturePath('EventLog/ValidRecordableEvent.php')], []);
    }

    #[Test]
    public function it_passes_when_class_does_not_implement_recordable(): void
    {
        $this->analyse([$this->getFixturePath('EventLog/ClassNotRecordable.php')], []);
    }

    #[Test]
    public function it_fails_when_recordable_is_missing_alias_attribute(): void
    {
        $this->analyse([$this->getFixturePath('EventLog/RecordableWithoutAlias.php')], [
            [
                class_basename(Recordable::class).' must have a #['.class_basename(Alias::class).'] attribute.',
                12,
            ],
        ]);
    }
}
