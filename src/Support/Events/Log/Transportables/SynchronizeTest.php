<?php

declare(strict_types=1);

namespace Support\Events\Log\Transportables;

use Illuminate\Support\Facades\Event;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Tests\Fixtures\Support\Amqp\Amqp;
use Tests\Fixtures\Support\Entities\Relayable\Events\Multiplexed;
use Tests\Fixtures\Support\Entities\Relayable\Events\Updated;
use Tests\Fixtures\Support\Entities\Relayable\Relayable;
use Tests\Fixtures\Support\Entities\Transportables\Transportable as ExtendedTransportable;
use Tests\Fixtures\Support\Mqtt\Mqtt;
use Tests\TestCase;
use Tooling\Composer\ClassMap\Cache;
use Tooling\EventLog\Composer\ClassMap\Collectors\Transports;

#[CoversClass(Synchronize::class)]
final class SynchronizeTest extends TestCase
{
    #[Test]
    public function it_records_a_row_per_discovered_transport(): void
    {
        Synchronize::make()->now();

        $this->assertEqualsCanonicalizing(
            resolve(Cache::class)->get(Transports::class),
            Transportable::pluck('class')->all(),
        );
    }

    #[Test]
    public function it_records_the_alias_and_transports_of_each_event(): void
    {
        Synchronize::make()->now();

        $transportable = Transportable::find((string) new Multiplexed(new Relayable)->alias);

        $this->assertNotNull($transportable);
        $this->assertSame(Multiplexed::class, $transportable->class);
        $this->assertEqualsCanonicalizing([Amqp::class, Mqtt::class], $transportable->transports->all());
    }

    #[Test]
    public function it_leaves_no_duplicates_when_run_repeatedly(): void
    {
        Synchronize::make()->now();
        $first = Transportable::pluck('alias')->all();

        Synchronize::make()->now();

        $this->assertEqualsCanonicalizing($first, Transportable::pluck('alias')->all());
    }

    #[Test]
    public function it_writes_through_the_model_given_to_use(): void
    {
        Transportable::use(ExtendedTransportable::class);

        Synchronize::make()->now();

        $this->assertInstanceOf(ExtendedTransportable::class, Transportable::using()::query()->first());
    }

    #[Test]
    public function it_removes_rows_that_are_no_longer_discovered(): void
    {
        Transportable::create([
            'alias' => 'relayable.retired',
            'class' => Updated::class,
            'transports' => [Mqtt::class],
        ]);

        Synchronize::make()->now();

        $this->assertNull(Transportable::find('relayable.retired'));
    }

    #[Test]
    public function it_announces_the_creation_of_a_newly_discovered_row(): void
    {
        Event::fake([Events\Created::class]);

        Synchronize::make()->now();

        Event::assertDispatched(Events\Created::class);
    }

    #[Test]
    public function it_announces_the_update_of_a_row_whose_event_changed(): void
    {
        Synchronize::make()->now();

        Transportable::query()->firstOrFail()->forceFill(['class' => 'App\\Stale'])->saveQuietly();

        Event::fake([Events\Updated::class]);

        Synchronize::make()->now();

        Event::assertDispatched(Events\Updated::class);
    }

    #[Test]
    public function it_announces_the_removal_of_a_row_that_is_no_longer_discovered(): void
    {
        Transportable::create([
            'alias' => 'relayable.retired',
            'class' => Updated::class,
            'transports' => [Mqtt::class],
        ]);

        Event::fake([Events\Deleting::class]);

        Synchronize::make()->now();

        Event::assertDispatched(
            Events\Deleting::class,
            fn (Events\Deleting $event): bool => $event->transportable->alias === 'relayable.retired',
        );
    }

    #[Test]
    public function it_announces_the_removal_through_the_model_given_to_use(): void
    {
        Transportable::use(ExtendedTransportable::class);

        ExtendedTransportable::create([
            'alias' => 'relayable.retired',
            'class' => Updated::class,
            'transports' => [Mqtt::class],
        ]);

        Event::fake([Events\Deleting::class]);

        Synchronize::make()->now();

        Event::assertDispatched(
            Events\Deleting::class,
            fn (Events\Deleting $event): bool => $event->transportable instanceof ExtendedTransportable,
        );
    }
}
