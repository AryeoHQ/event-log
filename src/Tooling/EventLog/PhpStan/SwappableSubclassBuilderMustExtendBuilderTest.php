<?php

declare(strict_types=1);

namespace Tooling\EventLog\PhpStan;

use Illuminate\Database\Eloquent\Attributes\UseEloquentBuilder;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Tests\Fixtures\Support\Swappable\Builder;
use Tests\Fixtures\Support\Swappable\Swappable;
use Tests\Tooling\Concerns\GetsFixtures;
use Tooling\EventLog\PhpStan\Collectors\SwappableSubclasses;

/** @extends RuleTestCase<SwappableSubclassBuilderMustExtendBuilder> */
#[CoversClass(SwappableSubclassBuilderMustExtendBuilder::class)]
#[CoversClass(SwappableSubclasses::class)]
final class SwappableSubclassBuilderMustExtendBuilderTest extends RuleTestCase
{
    use GetsFixtures;

    protected function getRule(): Rule
    {
        return new SwappableSubclassBuilderMustExtendBuilder;
    }

    protected function getCollectors(): array
    {
        return [new SwappableSubclasses(self::getContainer()->getByType(ReflectionProvider::class))];
    }

    #[Test]
    public function it_passes_when_the_named_class_extends_the_package_class(): void
    {
        $this->analyse([$this->getFixturePath('EventLog/SwappableSubclassWithAttributes.php')], []);
    }

    #[Test]
    public function it_passes_when_the_subclass_omits_the_attribute(): void
    {
        $this->analyse([$this->getFixturePath('EventLog/SwappableSubclassWithoutAttributes.php')], []);
    }

    #[Test]
    public function it_fails_when_the_named_class_is_not_ours(): void
    {
        $this->analyse([$this->getFixturePath('EventLog/SwappableSubclassWithForeignAttributes.php')], [
            ['A '.class_basename(Swappable::class).' subclass must point #['.class_basename(UseEloquentBuilder::class).'] at a class extending '.class_basename(Builder::class).'.', 18],
        ]);
    }
}
