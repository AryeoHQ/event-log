<?php

declare(strict_types=1);

namespace Support\Events\Database\Eloquent\Swappable\Swapper;

use Illuminate\Database\Eloquent\Attributes\CollectedBy;
use Illuminate\Database\Eloquent\Attributes\UseEloquentBuilder;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Support\Facades\Facade;
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
use Tests\Fixtures\Support\Swappable\ScopedSubclass;
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
    public function it_keeps_hook_attributes_on_the_origin(): void
    {
        $this->assertSame('hooked', (new Swappable)->getAttributes()['hooked']);
    }

    #[Test]
    public function it_keeps_hook_attributes_on_a_subclass(): void
    {
        $this->assertSame('hooked', (new Subclass)->getAttributes()['hooked']);
    }

    #[Test]
    public function it_keeps_hook_casts(): void
    {
        $this->assertSame('string', (new Subclass)->getCasts()['hooked']);
    }

    #[Test]
    public function it_lets_the_package_win_on_hook_casts(): void
    {
        $this->assertSame('string', (new Swappable)->getCasts()['name']);
        $this->assertSame('string', (new Subclass)->getCasts()['name']);
    }

    #[Test]
    public function it_lets_the_package_win_on_attribute_defaults(): void
    {
        $this->assertSame('origin', (new Subclass)->getAttributes()['name']);
    }

    #[Test]
    public function it_lets_the_package_win_on_hook_attributes(): void
    {
        $this->assertSame('origin', (new Swappable)->getAttributes()['name']);
        $this->assertSame('origin', (new Subclass)->getAttributes()['name']);
    }

    #[Test]
    public function it_keeps_filled_attributes(): void
    {
        $this->assertSame('filled', (new Subclass(['name' => 'filled']))->getAttributes()['name']);
    }

    #[Test]
    public function it_builds_a_clean_model(): void
    {
        $this->assertFalse((new Subclass)->isDirty());
    }

    #[Test]
    public function it_keeps_attributes_through_serialization(): void
    {
        $subclass = new Subclass;
        $subclass->setRawAttributes(['id' => 1, 'name' => 'stored'], true);

        $woken = unserialize(serialize($subclass));

        // Laravel re-runs the hook on wake; the package defaults must not follow it.
        $this->assertInstanceOf(Subclass::class, $woken);
        $this->assertSame(['id' => 1, 'name' => 'hooked', 'hooked' => 'hooked'], $woken->getAttributes());
    }

    #[Test]
    public function it_keeps_log_attributes_through_serialization(): void
    {
        $log = new Log;
        $log->setRawAttributes(['id' => 'id', 'status' => 'processed'], true);

        $woken = unserialize(serialize($log));

        $this->assertInstanceOf(Log::class, $woken);
        $this->assertSame(['id' => 'id', 'status' => 'processed'], $woken->getAttributes());
        $this->assertFalse($woken->isDirty());
    }

    #[Test]
    public function it_keeps_casts_through_serialization(): void
    {
        $woken = unserialize(serialize(new Subclass));

        $this->assertInstanceOf(Subclass::class, $woken);
        $this->assertSame('string', $woken->getCasts()['name']);
        $this->assertSame('boolean', $woken->getCasts()['extra']);
    }

    #[Test]
    public function it_keeps_events_through_serialization(): void
    {
        $woken = unserialize(serialize(new Subclass));

        $this->assertInstanceOf(Subclass::class, $woken);
        $this->assertSame(Events\SubclassCreated::class, $woken->dispatchesEvents()['created']);
    }

    #[Test]
    public function it_applies_defaults_after_a_wake(): void
    {
        unserialize(serialize(new Subclass));

        $this->assertSame('origin', (new Subclass)->getAttributes()['name']);
    }

    #[Test]
    public function it_keeps_stored_attributes_from_a_query(): void
    {
        Swappable::use(Subclass::class);

        Swappable::factory()->createQuietly(['name' => 'stored', 'hooked' => 'stored']);

        $fetched = Swappable::first();

        $this->assertInstanceOf(Subclass::class, $fetched);
        $this->assertSame('stored', $fetched->getAttributes()['name']);
        $this->assertSame('stored', $fetched->getAttributes()['hooked']);
        $this->assertFalse($fetched->isDirty());
    }

    #[Test]
    public function it_keeps_created_attributes_on_refresh(): void
    {
        $subclass = Subclass::query()->create(['name' => 'stored']);

        $this->assertSame('stored', $subclass->fresh()?->getAttributes()['name']);
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
    public function it_hydrates_the_swapped_class_from_a_query(): void
    {
        Swappable::use(Subclass::class);

        Swappable::factory()->createQuietly();

        $this->assertInstanceOf(Subclass::class, Swappable::first());
    }

    #[Test]
    public function it_applies_the_swapped_class_scopes_to_a_query(): void
    {
        Swappable::use(ScopedSubclass::class);

        Swappable::factory()->createQuietly(['name' => 'visible']);
        Swappable::factory()->createQuietly(['name' => 'hidden']);

        $fetched = Swappable::all();

        $this->assertCount(1, $fetched);
        $this->assertContainsOnlyInstancesOf(ScopedSubclass::class, $fetched);
        $this->assertSame('visible', $fetched->first()?->getAttributes()['name']);
    }

    #[Test]
    public function it_survives_scoped_instance_reset(): void
    {
        Swappable::use(Subclass::class);

        $this->app->forgetScopedInstances();
        Facade::clearResolvedInstances();

        $this->assertSame(Subclass::class, Swappable::using());
    }

    #[Test]
    public function it_rejects_a_foreign_event(): void
    {
        $this->expectException(Invalid::class);

        new SubclassWithForeignEvent;
    }
}
