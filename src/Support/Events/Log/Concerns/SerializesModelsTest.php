<?php

declare(strict_types=1);

namespace Support\Events\Log\Concerns;

use Illuminate\Contracts\Database\ModelIdentifier;
use Illuminate\Support\Facades\Event;
use PHPUnit\Framework\Attributes\CoversTrait;
use PHPUnit\Framework\Attributes\Test;
use Tests\Fixtures\Support\Entities\Articles\Article;
use Tests\Fixtures\Support\Entities\Articles\Events\Created;
use Tests\Fixtures\Support\Entities\Articles\Events\DisablesModelSerialization;
use Tests\Fixtures\Support\Entities\Articles\Events\DisablesModelSerializationThroughInterface;
use Tests\TestCase;

#[CoversTrait(SerializesModels::class)]
final class SerializesModelsTest extends TestCase
{
    #[Test]
    public function it_serializes_entity_as_model_identifier_by_default(): void
    {
        $event = new Created(Article::factory()->make());

        $this->assertInstanceOf(
            ModelIdentifier::class,
            data_get($event->__serialize(), 'entity'),
        );
    }

    #[Test]
    public function it_serializes_entity_as_raw_model_when_model_serialization_is_disabled(): void
    {
        $serialized = with( // @phpstan-ignore argument.templateType
            new Created(Article::factory()->make()),
            fn (Created $event) => Event::withoutSerializesModels( // @phpstan-ignore staticMethod.notFound
                fn () => $event->__serialize()
            )
        );

        $this->assertInstanceOf(Article::class, data_get($serialized, 'entity'));
    }

    #[Test]
    public function it_round_trips_when_model_serialization_is_disabled(): void
    {
        $article = Article::factory()->make();

        $serialized = with( // @phpstan-ignore argument.templateType
            new Created($article),
            fn (Created $event) => Event::withoutSerializesModels( // @phpstan-ignore staticMethod.notFound
                fn () => serialize($event),
            )
        );

        $this->assertTrue(
            $article->is(
                unserialize($serialized)->entity
            )
        );
    }

    #[Test]
    public function it_preserves_disabled_model_serialization_across_multiple_cycles(): void
    {
        $notPersisted = Article::factory()->make();

        $first = with( // @phpstan-ignore argument.templateType
            new Created($notPersisted),
            fn (Created $event) => Event::withoutSerializesModels( // @phpstan-ignore staticMethod.notFound
                fn () => serialize($event),
            )
        );

        $second = with(
            unserialize($first),
            fn (Created $event) => serialize($event)
        );

        $this->assertInstanceOf(
            Article::class,
            unserialize($second)->entity,
        );
    }

    #[Test]
    public function it_restores_model_serialization_after_scope_ends(): void
    {
        Event::withoutSerializesModels(fn () => null); // @phpstan-ignore staticMethod.notFound

        $event = new Created(Article::factory()->make());

        $this->assertInstanceOf(
            ModelIdentifier::class,
            data_get($event->__serialize(), 'entity'),
        );
    }

    #[Test]
    public function it_disables_serialization_for_a_filtered_event_class(): void
    {
        $serialized = with( // @phpstan-ignore argument.templateType
            new DisablesModelSerialization(Article::factory()->make()),
            fn (DisablesModelSerialization $event) => Event::withoutSerializesModels( // @phpstan-ignore staticMethod.notFound
                DisablesModelSerialization::class,
                fn () => $event->__serialize(),
            )
        );

        $this->assertInstanceOf(Article::class, data_get($serialized, 'entity'));
    }

    #[Test]
    public function it_preserves_serialization_for_non_matching_filtered_event(): void
    {
        $serialized = with( // @phpstan-ignore argument.templateType
            new Created(Article::factory()->make()),
            fn (Created $event) => Event::withoutSerializesModels( // @phpstan-ignore staticMethod.notFound
                DisablesModelSerialization::class,
                fn () => $event->__serialize(),
            )
        );

        $this->assertInstanceOf(ModelIdentifier::class, data_get($serialized, 'entity'));
    }

    #[Test]
    public function it_disables_serialization_for_events_matching_an_interface(): void
    {
        $serialized = with( // @phpstan-ignore argument.templateType
            new DisablesModelSerializationThroughInterface(Article::factory()->make()),
            fn (DisablesModelSerializationThroughInterface $event) => Event::withoutSerializesModels( // @phpstan-ignore staticMethod.notFound
                \Stringable::class,
                fn () => $event->__serialize(),
            )
        );

        $this->assertInstanceOf(Article::class, data_get($serialized, 'entity'));
    }
}
