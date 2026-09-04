<?php

declare(strict_types=1);

namespace Tooling\EventLog\PhpStan;

use Illuminate\Database\Eloquent\Model;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Support\Events\Log\Contracts\Loggable;
use Support\Events\Log\IdentifiesLoggable\IdentifiesLoggable;
use Tests\Tooling\Concerns\GetsFixtures;

/** @extends RuleTestCase<IdentifiesLoggableMustBeLoggableModel> */
#[CoversClass(IdentifiesLoggableMustBeLoggableModel::class)]
final class IdentifiesLoggableMustBeLoggableModelTest extends RuleTestCase
{
    use GetsFixtures;

    protected function getRule(): Rule
    {
        return new IdentifiesLoggableMustBeLoggableModel;
    }

    #[Test]
    public function it_passes_when_property_type_is_loggable_model(): void
    {
        $this->analyse([$this->getFixturePath('EventLog/ValidRecordableEvent.php')], []);
    }

    #[Test]
    public function it_passes_when_class_does_not_implement_recordable(): void
    {
        $this->analyse([$this->getFixturePath('EventLog/ClassNotRecordable.php')], []);
    }

    #[Test]
    public function it_fails_when_property_type_is_not_loggable_model(): void
    {
        $this->analyse([$this->getFixturePath('EventLog/HasLoggableWithInvalidIdentifiesLoggableType.php')], [
            [
                'The #['.class_basename(IdentifiesLoggable::class).'] property must be typed as '.class_basename(Model::class).' & '.class_basename(Loggable::class).'.',
                15,
            ],
        ]);
    }
}
