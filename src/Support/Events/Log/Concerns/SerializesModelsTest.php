<?php

declare(strict_types=1);

namespace Support\Events\Log\Concerns;

use Illuminate\Contracts\Database\ModelIdentifier;
use Illuminate\Support\Facades\Event;
use PHPUnit\Framework\Attributes\CoversTrait;
use PHPUnit\Framework\Attributes\Test;
use Tests\Fixtures\Support\Entities\Articles\Article;
use Tests\Fixtures\Support\Entities\Articles\Events\Updated;
use Tests\Fixtures\Support\Entities\Tags\Events\DisablesModelSerialization;
use Tests\Fixtures\Support\Entities\Tags\Events\DisablesModelSerializationThroughInterface;
use Tests\Fixtures\Support\Entities\Tags\Tag;
use Tests\TestCase;

#[CoversTrait(SerializesModels::class)]
final class SerializesModelsTest extends TestCase
{
    #[Test]
    public function it_serializes_as_model_identifier_by_default(): void
    {
        $event = new Updated(Article::factory()->make());

        $this->assertInstanceOf(
            ModelIdentifier::class,
            data_get($event->__serialize(), 'article'),
        );
    }

    #[Test]
    public function it_serializes_as_raw_model_when_serialization_is_disabled(): void
    {
        $serialized = with(
            new Updated(Article::factory()->make()),
            fn (Updated $event) => Event::withoutSerializesModels(
                fn () => $event->__serialize()
            )
        );

        $this->assertInstanceOf(Article::class, data_get($serialized, 'article'));
    }

    #[Test]
    public function it_round_trips_when_serialization_is_disabled(): void
    {
        $article = Article::factory()->make();

        $serialized = with(
            new Updated($article),
            fn (Updated $event) => Event::withoutSerializesModels(
                fn () => serialize($event),
            )
        );

        $this->assertTrue(
            $article->is(unserialize($serialized)->article)
        );
    }

    #[Test]
    public function it_preserves_disabled_serialization_across_multiple_cycles(): void
    {
        $notPersisted = Article::factory()->make();

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

        $this->assertInstanceOf(Article::class, unserialize($second)->article);
    }

    #[Test]
    public function it_restores_serialization_after_scope_ends(): void
    {
        Event::withoutSerializesModels(fn () => null);

        $event = new Updated(Article::factory()->make());

        $this->assertInstanceOf(
            ModelIdentifier::class,
            data_get($event->__serialize(), 'article'),
        );
    }

    #[Test]
    public function it_disables_serialization_for_a_filtered_event_class(): void
    {
        $serialized = with(
            new DisablesModelSerialization(Tag::factory()->make()),
            fn (DisablesModelSerialization $event) => Event::withoutSerializesModels(
                DisablesModelSerialization::class,
                fn () => $event->__serialize(),
            )
        );

        $this->assertInstanceOf(Tag::class, data_get($serialized, 'tag'));
    }

    #[Test]
    public function it_preserves_serialization_for_non_matching_filtered_event(): void
    {
        $serialized = with(
            new Updated(Article::factory()->make()),
            fn (Updated $event) => Event::withoutSerializesModels(
                DisablesModelSerialization::class,
                fn () => $event->__serialize(),
            )
        );

        $this->assertInstanceOf(ModelIdentifier::class, data_get($serialized, 'article'));
    }

    #[Test]
    public function it_disables_serialization_for_events_matching_an_interface(): void
    {
        $serialized = with(
            new DisablesModelSerializationThroughInterface(Tag::factory()->make()),
            fn (DisablesModelSerializationThroughInterface $event) => Event::withoutSerializesModels(
                \Stringable::class,
                fn () => $event->__serialize(),
            )
        );

        $this->assertInstanceOf(Tag::class, data_get($serialized, 'tag'));
    }
}
