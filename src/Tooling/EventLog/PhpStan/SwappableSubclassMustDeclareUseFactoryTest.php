<?php

declare(strict_types=1);

namespace Tooling\EventLog\PhpStan;

use Illuminate\Database\Eloquent\Attributes\UseFactory;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Tests\Fixtures\Support\Swappable\Swappable;
use Tests\Tooling\Concerns\GetsFixtures;
use Tooling\EventLog\PhpStan\Collectors\SwappableSubclasses;

/** @extends RuleTestCase<SwappableSubclassMustDeclareUseFactory> */
#[CoversClass(SwappableSubclassMustDeclareUseFactory::class)]
#[CoversClass(SwappableSubclasses::class)]
final class SwappableSubclassMustDeclareUseFactoryTest extends RuleTestCase
{
    use GetsFixtures;

    protected function getRule(): Rule
    {
        return new SwappableSubclassMustDeclareUseFactory;
    }

    protected function getCollectors(): array
    {
        return [new SwappableSubclasses(self::getContainer()->getByType(ReflectionProvider::class))];
    }

    #[Test]
    public function it_passes_when_the_subclass_declares_the_attribute(): void
    {
        $this->analyse([$this->getFixturePath('EventLog/SwappableSubclassWithAttributes.php')], []);
    }

    #[Test]
    public function it_passes_when_a_class_does_not_extend_a_model(): void
    {
        $this->analyse([$this->getFixturePath('EventLog/ClassNotRecordable.php')], []);
    }

    #[Test]
    public function it_fails_when_the_subclass_omits_the_attribute(): void
    {
        $this->analyse([$this->getFixturePath('EventLog/SwappableSubclassWithoutAttributes.php')], [
            ['A '.class_basename(Swappable::class).' subclass must declare #['.class_basename(UseFactory::class).'].', 9],
        ]);
    }
}
