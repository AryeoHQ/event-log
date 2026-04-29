<?php

declare(strict_types=1);

namespace Support\Events\Log\Provides;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversTrait;
use PHPUnit\Framework\Attributes\Test;
use ReflectionClass;
use ReflectionProperty;
use Support\Events\Log\Alias;
use Support\Events\Log\Contracts\Loggable;
use Support\Events\Log\IdentifiesLoggable;
use Tests\Fixtures\Support\Entities\Articles\Article;
use Tests\Fixtures\Support\Entities\Articles\Events\Updated;
use Tests\Fixtures\Tooling\EventLog\HasLoggableWithInvalidIdentifiesLoggableType;
use Tests\Fixtures\Tooling\EventLog\HasLoggableWithMultipleIdentifiesLoggableAttributes;
use Tests\Fixtures\Tooling\EventLog\HasLoggableWithoutIdentifiesLoggableAttribute;
use Tests\Fixtures\Tooling\EventLog\RecordableWithoutAlias;
use Tests\TestCase;

#[CoversClass(Alias\Exceptions\NotDefined::class)]
#[CoversClass(Alias\Alias::class)]
#[CoversClass(IdentifiesLoggable\Exceptions\MultipleDefined::class)]
#[CoversClass(IdentifiesLoggable\Exceptions\NotDefined::class)]
#[CoversClass(IdentifiesLoggable\Exceptions\NotLoggable::class)]
#[CoversTrait(HasLoggable::class)]
final class HasLoggableTest extends TestCase
{
    #[Test]
    public function it_uses_the_has_loggable_trait(): void
    {
        $this->assertContains(
            HasLoggable::class,
            collect(
                new ReflectionClass(Updated::class)->getTraits()
            )->keys(),
        );
    }

    #[Test]
    public function it_exposes_the_loggable(): void
    {
        $article = Article::factory()->make();

        $event = new Updated($article);

        $this->assertInstanceOf(Loggable::class, $event->loggable);
        $this->assertSame($article, $event->loggable);
    }

    #[Test]
    public function it_is_the_same_memory_address_for_loggable_and_identifies_loggable_property(): void
    {
        $event = new Updated(Article::factory()->make());

        $this->assertSame($event->loggable, $event->{$event->loggableProperty});
    }

    #[Test]
    public function it_derives_alias_from_attribute(): void
    {
        $event = new Updated(Article::factory()->make());

        $this->assertSame('article.updated', $event->alias->toString());
    }

    #[Test]
    public function it_throws_when_alias_attribute_is_missing(): void
    {
        $this->expectException(Alias\Exceptions\NotDefined::class);

        $event = new RecordableWithoutAlias(Article::factory()->make());
        $event->alias; // @phpstan-ignore expr.resultUnused
    }

    #[Test]
    public function it_throws_when_no_property_defines_identifies_loggable(): void
    {
        $this->expectException(IdentifiesLoggable\Exceptions\NotDefined::class);

        $event = new HasLoggableWithoutIdentifiesLoggableAttribute;
        new ReflectionProperty($event, 'loggableProperty')->getValue($event);
    }

    #[Test]
    public function it_throws_when_multiple_identifies_loggable_properties_are_defined(): void
    {
        $this->expectException(IdentifiesLoggable\Exceptions\MultipleDefined::class);

        $event = new HasLoggableWithMultipleIdentifiesLoggableAttributes;
        new ReflectionProperty($event, 'loggableProperty')->getValue($event);
    }

    #[Test]
    public function it_throws_when_identifies_loggable_property_is_not_loggable(): void
    {
        $this->expectException(IdentifiesLoggable\Exceptions\NotLoggable::class);

        $event = new HasLoggableWithInvalidIdentifiesLoggableType;
        new ReflectionProperty($event, 'loggableProperty')->getValue($event);
    }

    #[Test]
    public function it_registers_loggable_as_virtual_so_it_is_not_serialized(): void
    {
        $property = new ReflectionProperty(HasLoggable::class, 'loggable');

        $this->assertTrue(
            $property->isVirtual(),
            '`$loggable` is just a convenience accessor and should not be included in serialization.'
        );
    }

    #[Test]
    public function it_registers_loggable_property_backed_so_it_is_serialized(): void
    {
        $property = new ReflectionProperty(HasLoggable::class, 'loggableProperty');

        $this->assertFalse(
            $property->isVirtual(),
            '`$loggableProperty` should be serialized so the Reflection lookup is not repeated after deserialization.'
        );
    }

    #[Test]
    public function it_always_evaluates_loggable_property_lookup(): void
    {
        $event = new Updated(Article::factory()->make());
        $property = new ReflectionProperty($event, 'loggableProperty');

        $this->assertNotEmpty(
            $property->getValue($event),
        );
    }
}
