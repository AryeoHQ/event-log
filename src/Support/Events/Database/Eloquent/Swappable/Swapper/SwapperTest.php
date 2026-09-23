<?php

declare(strict_types=1);

namespace Support\Events\Database\Eloquent\Swappable\Swapper;

use Illuminate\Database\Eloquent\Attributes\CollectedBy;
use Illuminate\Database\Eloquent\Attributes\UseEloquentBuilder;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Support\Events\Database\Eloquent\Swappable\Swapper\Exceptions\Invalid;
use Support\Events\Database\Eloquent\Swappable\Swapper\Exceptions\MissingAttribute;
use Support\Events\Database\Eloquent\Swappable\Swapper\Exceptions\NotSwappable;
use Support\Events\Log\Logs\Log;
use Support\Events\Log\Relays\Relay;
use Tests\Fixtures\Support\Entities\Recordable\Recordable;
use Tests\Fixtures\Support\Swappable\Builder;
use Tests\Fixtures\Support\Swappable\Collection\Swappables;
use Tests\Fixtures\Support\Swappable\Events;
use Tests\Fixtures\Support\Swappable\Factory;
use Tests\Fixtures\Support\Swappable\Subclass;
use Tests\Fixtures\Support\Swappable\SubclassWithForeignEvent;
use Tests\Fixtures\Support\Swappable\Swappable;
use Tests\Fixtures\Tooling\EventLog\SwappableSubclassWithForeignAttributes;
use Tests\Fixtures\Tooling\EventLog\SwappableSubclassWithoutAttributes;
use Tests\TestCase;

#[CoversClass(Swapper::class)]
#[CoversClass(Facades\Swapper::class)]
#[CoversClass(Invalid::class)]
#[CoversClass(MissingAttribute::class)]
#[CoversClass(NotSwappable::class)]
final class SwapperTest extends TestCase
{
    #[Test]
    public function it_uses_the_original_by_default(): void
    {
        $this->assertSame(
            Swappable::class,
            Facades\Swapper::using(Swappable::class)
        );
    }

    #[Test]
    public function it_uses_the_replacement(): void
    {
        Facades\Swapper::replace(Swappable::class, Subclass::class);

        $this->assertSame(Subclass::class, Facades\Swapper::using(Swappable::class));
    }

    /**
     * Registering is scoped to the container, so the previous test's swap has
     * to be gone without anything resetting it.
     */
    #[Test]
    public function it_resets_between_tests(): void
    {
        $this->assertSame(Swappable::class, Facades\Swapper::using(Swappable::class));
    }

    #[Test]
    public function it_requires_a_subclass(): void
    {
        $this->expectException(Invalid::class);

        Facades\Swapper::replace(Log::class, Relay::class);
    }

    #[Test]
    public function it_requires_a_swappable_model(): void
    {
        $this->expectException(NotSwappable::class);

        Facades\Swapper::replace(Recordable::class, Log::class); // @phpstan-ignore argument.type
    }

    #[Test]
    public function it_resolves_the_builder(): void
    {
        $this->assertInstanceOf(Builder::class, Swappable::query());
    }

    #[Test]
    public function it_resolves_the_collection(): void
    {
        $this->assertInstanceOf(Swappables::class, (new Swappable)->newCollection());
    }

    #[Test]
    public function it_resolves_the_factory(): void
    {
        $this->assertInstanceOf(Factory::class, Swappable::factory());
    }

    #[Test]
    public function it_rejects_a_foreign_builder(): void
    {
        $this->expectException(Invalid::class);

        SwappableSubclassWithForeignAttributes::query();
    }

    #[Test]
    public function it_rejects_a_foreign_collection(): void
    {
        $this->expectException(Invalid::class);

        (new SwappableSubclassWithForeignAttributes)->newCollection();
    }

    #[Test]
    public function it_rejects_a_foreign_factory(): void
    {
        $this->expectException(Invalid::class);

        SwappableSubclassWithForeignAttributes::factory();
    }

    #[Test]
    public function it_requires_a_builder_attribute(): void
    {
        $this->expectException(MissingAttribute::class);

        SwappableSubclassWithoutAttributes::query();
    }

    #[Test]
    public function it_requires_a_collection_attribute(): void
    {
        $this->expectException(MissingAttribute::class);

        (new SwappableSubclassWithoutAttributes)->newCollection();
    }

    #[Test]
    public function it_requires_a_factory_attribute(): void
    {
        $this->expectException(MissingAttribute::class);

        SwappableSubclassWithoutAttributes::factory();
    }

    #[Test]
    public function it_consolidates_maps(): void
    {
        $subclass = new Subclass;

        $this->assertArrayHasKey('name', $subclass->getCasts());
        $this->assertArrayHasKey('extra', $subclass->getCasts());
    }

    #[Test]
    public function it_lets_the_package_win_on_casts(): void
    {
        $subclass = new Subclass;

        $this->assertSame('string', $subclass->getCasts()['name']);
    }

    #[Test]
    public function it_lets_the_consumer_win_on_events(): void
    {
        $subclass = new Subclass;

        $this->assertSame(Events\SubclassCreated::class, $subclass->dispatchesEvents()['created']);
    }

    #[Test]
    public function it_consolidates_lists(): void
    {
        $subclass = new Subclass;

        $this->assertContains('name', $subclass->getFillable());
    }

    #[Test]
    public function it_reads_attribute_declarations(): void
    {
        $this->assertSame(Builder::class, Facades\Swapper::declared(Swappable::class, UseEloquentBuilder::class));
        $this->assertSame(Factory::class, Facades\Swapper::declared(Swappable::class, UseFactory::class));
        $this->assertSame(Swappables::class, Facades\Swapper::declared(Swappable::class, CollectedBy::class));
    }

    #[Test]
    public function it_requires_an_attribute(): void
    {
        $this->expectException(MissingAttribute::class);

        Facades\Swapper::declared(SwappableSubclassWithoutAttributes::class, UseFactory::class);
    }

    #[Test]
    public function it_finds_the_origin(): void
    {
        $this->assertSame(Swappable::class, Facades\Swapper::origin(Subclass::class));
        $this->assertSame(Swappable::class, Facades\Swapper::origin(Swappable::class));
    }

    #[Test]
    public function it_identifies_swappable_models(): void
    {
        $this->assertTrue(Facades\Swapper::isSwappable(Swappable::class));
        $this->assertFalse(Facades\Swapper::isSwappable(Recordable::class));
    }

    #[Test]
    public function it_accepts_inherited_events(): void
    {
        $subclass = new Subclass;

        $this->assertArrayHasKey('created', $subclass->dispatchesEvents());
    }

    #[Test]
    public function it_rejects_a_foreign_event(): void
    {
        $this->expectException(Invalid::class);

        new SubclassWithForeignEvent;
    }
}
