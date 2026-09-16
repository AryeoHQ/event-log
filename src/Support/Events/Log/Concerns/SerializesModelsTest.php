<?php

declare(strict_types=1);

namespace Support\Events\Log\Concerns;

use Illuminate\Contracts\Database\ModelIdentifier;
use Illuminate\Support\Facades\Event;
use PHPUnit\Framework\Attributes\CoversTrait;
use PHPUnit\Framework\Attributes\Test;
use Tests\Fixtures\Support\Entities\DisablesModelSerialization\Events\DisablesModelSerialization;
use Tests\Fixtures\Support\Entities\DisablesModelSerializationThroughInterface\Events\DisablesModelSerializationThroughInterface;
use Tests\Fixtures\Support\Entities\NonRecordable\NonRecordable;
use Tests\Fixtures\Support\Entities\Recordable\Events\Updated;
use Tests\Fixtures\Support\Entities\Recordable\Recordable;
use Tests\TestCase;

#[CoversTrait(SerializesModels::class)]
final class SerializesModelsTest extends TestCase
{
    #[Test]
    public function it_serializes_as_model_identifier_by_default(): void
    {
        $event = new Updated(Recordable::factory()->make());

        $this->assertInstanceOf(
            ModelIdentifier::class,
            data_get($event->__serialize(), 'recordable'),
        );
    }

    #[Test]
    public function it_serializes_as_raw_model_when_serialization_is_disabled(): void
    {
        $serialized = with(
            new Updated(Recordable::factory()->make()),
            fn (Updated $event) => Event::withoutSerializesModels(
                fn () => $event->__serialize()
            )
        );

        $this->assertInstanceOf(Recordable::class, data_get($serialized, 'recordable'));
    }

    #[Test]
    public function it_round_trips_when_serialization_is_disabled(): void
    {
        $recordable = Recordable::factory()->make();

        $serialized = with(
            new Updated($recordable),
            fn (Updated $event) => Event::withoutSerializesModels(
                fn () => serialize($event),
            )
        );

        $this->assertTrue(
            $recordable->is(unserialize($serialized)->recordable)
        );
    }

    #[Test]
    public function it_preserves_disabled_serialization_across_multiple_cycles(): void
    {
        $notPersisted = Recordable::factory()->make();

        $first = with(
            new Updated($notPersisted),
            fn (Updated $event) => Event::withoutSerializesModels(
                fn () => serialize($event),
            )
        );

        $second = with(
            unserialize($first),
            fn (Updated $event) => serialize($event)
        );

        $this->assertInstanceOf(Recordable::class, unserialize($second)->recordable);
    }

    #[Test]
    public function it_restores_serialization_after_scope_ends(): void
    {
        Event::withoutSerializesModels(fn () => null);

        $event = new Updated(Recordable::factory()->make());

        $this->assertInstanceOf(
            ModelIdentifier::class,
            data_get($event->__serialize(), 'recordable'),
        );
    }

    #[Test]
    public function it_disables_serialization_for_a_filtered_event_class(): void
    {
        $serialized = with(
            new DisablesModelSerialization(NonRecordable::factory()->make()),
            fn (DisablesModelSerialization $event) => Event::withoutSerializesModels(
                DisablesModelSerialization::class,
                fn () => $event->__serialize(),
            )
        );

        $this->assertInstanceOf(NonRecordable::class, data_get($serialized, 'nonRecordable'));
    }

    #[Test]
    public function it_preserves_serialization_for_non_matching_filtered_event(): void
    {
        $serialized = with(
            new Updated(Recordable::factory()->make()),
            fn (Updated $event) => Event::withoutSerializesModels(
                DisablesModelSerialization::class,
                fn () => $event->__serialize(),
            )
        );

        $this->assertInstanceOf(ModelIdentifier::class, data_get($serialized, 'recordable'));
    }

    #[Test]
    public function it_disables_serialization_for_events_matching_an_interface(): void
    {
        $serialized = with(
            new DisablesModelSerializationThroughInterface(NonRecordable::factory()->make()),
            fn (DisablesModelSerializationThroughInterface $event) => Event::withoutSerializesModels(
                \Stringable::class,
                fn () => $event->__serialize(),
            )
        );

        $this->assertInstanceOf(NonRecordable::class, data_get($serialized, 'nonRecordable'));
    }
}
