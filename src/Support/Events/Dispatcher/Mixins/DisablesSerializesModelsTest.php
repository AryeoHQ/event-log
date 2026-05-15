<?php

declare(strict_types=1);

namespace Support\Events\Dispatcher\Mixins;

use Illuminate\Contracts\Database\ModelIdentifier;
use Illuminate\Support\Facades\Event;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Tests\Fixtures\Support\Entities\Articles\Article;
use Tests\Fixtures\Support\Entities\Articles\Events\Updated;
use Tests\Fixtures\Support\Entities\Tags\Events\DisablesModelSerialization;
use Tests\Fixtures\Support\Entities\Tags\Events\DisablesModelSerializationThroughInterface;
use Tests\Fixtures\Support\Entities\Tags\Tag;
use Tests\TestCase;

#[CoversClass(DisablesSerializesModels::class)]
final class DisablesSerializesModelsTest extends TestCase
{
    #[Test]
    public function it_serializes_loggable_property(): void
    {
        $serialized = null;

        Event::withoutSerializesModels(function () use (&$serialized) {
            $event = new Updated(Article::factory()->make());
            $serialized = $event->__serialize();
        });

        $this->assertSame('article', data_get($serialized, 'loggableProperty'));
    }

    #[Test]
    public function it_disables_model_serialization_within_the_closure(): void
    {
        $serialized = null;

        Event::withoutSerializesModels(function () use (&$serialized) {
            $event = new Updated(Article::factory()->make());
            $serialized = $event->__serialize();
        });

        $loggableProperty = data_get($serialized, 'loggableProperty');

        $this->assertInstanceOf(Article::class, data_get($serialized, $loggableProperty));
    }

    #[Test]
    public function it_returns_the_closure_result(): void
    {
        $article = Article::factory()->make();

        $event = Event::withoutSerializesModels(fn () => new Updated($article));

        $this->assertInstanceOf(Updated::class, $event);
    }

    #[Test]
    public function it_restores_model_serialization_after_the_closure(): void
    {
        Event::withoutSerializesModels(function () {
            // noop
        });

        $event = new Updated(Article::factory()->make());
        $serialized = $event->__serialize();

        $this->assertInstanceOf(
            ModelIdentifier::class,
            data_get($serialized, data_get($serialized, 'loggableProperty')),
        );
    }

    #[Test]
    public function it_restores_model_serialization_even_when_closure_throws(): void
    {
        try {
            Event::withoutSerializesModels(function () {
                throw new \RuntimeException('test');
            });
        } catch (\RuntimeException) {
            // expected
        }

        $event = new Updated(Article::factory()->make());
        $serialized = $event->__serialize();

        $this->assertInstanceOf(
            ModelIdentifier::class,
            data_get($serialized, data_get($serialized, 'loggableProperty')),
        );
    }

    #[Test]
    public function it_disables_model_serialization_for_a_specific_event_class(): void
    {
        $serialized = null;

        Event::withoutSerializesModels(DisablesModelSerialization::class, function () use (&$serialized) {
            $event = new DisablesModelSerialization(Tag::factory()->make());
            $serialized = $event->__serialize();
        });

        $this->assertInstanceOf(
            Tag::class,
            data_get($serialized, data_get($serialized, 'loggableProperty')),
        );
    }

    #[Test]
    public function it_does_not_disable_model_serialization_for_non_matching_event_class(): void
    {
        $serialized = null;

        Event::withoutSerializesModels(DisablesModelSerialization::class, function () use (&$serialized) {
            $event = new Updated(Article::factory()->make());
            $serialized = $event->__serialize();
        });

        $this->assertInstanceOf(
            ModelIdentifier::class,
            data_get($serialized, data_get($serialized, 'loggableProperty')),
        );
    }

    #[Test]
    public function it_disables_model_serialization_for_an_array_of_event_classes(): void
    {
        $first = null;
        $second = null;

        Event::withoutSerializesModels(
            [DisablesModelSerialization::class, DisablesModelSerializationThroughInterface::class],
            function () use (&$first, &$second) {
                $first = (new DisablesModelSerialization(Tag::factory()->make()))->__serialize();
                $second = (new DisablesModelSerializationThroughInterface(Tag::factory()->make()))->__serialize();
            },
        );

        $this->assertInstanceOf(
            Tag::class,
            data_get($first, data_get($first, 'loggableProperty')),
        );
        $this->assertInstanceOf(
            Tag::class,
            data_get($second, data_get($second, 'loggableProperty')),
        );
    }

    #[Test]
    public function it_disables_model_serialization_for_events_matching_an_interface(): void
    {
        $serialized = null;

        Event::withoutSerializesModels(\Stringable::class, function () use (&$serialized) {
            $event = new DisablesModelSerializationThroughInterface(Tag::factory()->make());
            $serialized = $event->__serialize();
        });

        $this->assertInstanceOf(
            Tag::class,
            data_get($serialized, data_get($serialized, 'loggableProperty')),
        );
    }

    #[Test]
    public function it_does_not_disable_model_serialization_for_events_not_matching_the_interface(): void
    {
        $serialized = null;

        Event::withoutSerializesModels(\Stringable::class, function () use (&$serialized) {
            $event = new Updated(Article::factory()->make());
            $serialized = $event->__serialize();
        });

        $this->assertInstanceOf(
            ModelIdentifier::class,
            data_get($serialized, data_get($serialized, 'loggableProperty')),
        );
    }

    #[Test]
    public function it_restores_filtered_model_serialization_after_the_closure(): void
    {
        Event::withoutSerializesModels(DisablesModelSerialization::class, function () {
            // noop
        });

        $event = new DisablesModelSerialization(Tag::factory()->make());
        $serialized = $event->__serialize();

        $this->assertInstanceOf(
            ModelIdentifier::class,
            data_get($serialized, data_get($serialized, 'loggableProperty')),
        );
    }

    #[Test]
    public function it_throws_when_filtering_by_event_class_without_a_callback(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('A callback is required when disabling by event class.');

        Event::withoutSerializesModels(DisablesModelSerialization::class);
    }
}
