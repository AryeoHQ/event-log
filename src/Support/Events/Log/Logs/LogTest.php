<?php

declare(strict_types=1);

namespace Support\Events\Log\Logs;

use Illuminate\Database\ClassMorphViolationException;
use Illuminate\Database\Eloquent\MassAssignmentException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Support\Events\Log\Exceptions\IntegrityViolation;
use Tests\Fixtures\Support\Entities\Articles\Article;
use Tests\Fixtures\Support\Entities\Articles\Events\Updated;
use Tests\Fixtures\Support\Entities\Articles\Events\Updating;
use Tests\Fixtures\Support\Entities\Categories\Category;
use Tests\Fixtures\Support\Entities\Comments\Comment;
use Tests\Fixtures\Support\Entities\NoMorphMap;
use Tests\Fixtures\Support\Entities\Notes\Note;
use Tests\Fixtures\Support\Entities\Tags\Tag;
use Tests\TestCase;
use TypeError;

#[CoversClass(Log::class)]
final class LogTest extends TestCase
{
    #[Test]
    public function it_sets_event_when_recordable_received(): void
    {
        $log = Log::make()->fill([
            'event' => new Updated(Article::factory()->create()),
        ]);

        $this->assertInstanceOf(Updated::class, $log->event);
    }

    #[Test]
    public function it_round_trips_event_with_hmac(): void
    {
        $log = Log::create([
            'event' => new Updated(Article::factory()->create()),
            'context' => Context::getFacadeRoot(),
            'idempotency_key' => Str::uuid7()->toString(),
            'occurred_at' => now(),
        ]);

        $this->assertInstanceOf(Updated::class, $log->fresh()->event);
    }

    #[Test]
    public function it_throws_when_event_payload_is_tampered(): void
    {
        $this->expectException(IntegrityViolation::class);

        $log = Log::create([
            'event' => new Updated(Article::factory()->create()),
            'context' => Context::getFacadeRoot(),
            'idempotency_key' => Str::uuid7()->toString(),
            'occurred_at' => now(),
        ]);

        $tampered = with(
            $log->getRawOriginal('event'),
            fn (string $raw) => substr($raw, 0, 64).'TAMPERED'.substr($raw, 72)
        );
        $log->setRawAttributes(array_merge($log->getAttributes(), ['event' => $tampered]));

        $log->event; // @phpstan-ignore expr.resultUnused
    }

    #[Test]
    public function it_throws_when_event_is_set_as_unsupported_type(): void
    {
        $this->expectException(TypeError::class);

        Model::preventSilentlyDiscardingAttributes();
        Log::make()->fill([
            'event' => new class {},
        ]);
    }

    #[Test]
    public function it_throws_when_context_is_set_as_unsupported_type(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        Model::preventSilentlyDiscardingAttributes();
        Log::make()->fill([
            'context' => 'not-a-repository',
        ]);
    }

    #[Test]
    public function it_sets_context_when_context_received(): void
    {
        $log = Log::make()->fill([
            'context' => app(\Illuminate\Log\Context\Repository::class)->add(['foo' => 'bar']),
        ]);

        $this->assertInstanceOf(\Illuminate\Log\Context\Repository::class, $log->context);
    }

    #[Test]
    public function it_sets_context_when_whitelisted_received(): void
    {
        $log = Log::make()->fill([
            'context' => new \Support\Events\Log\Context\Whitelisted,
        ]);

        $this->assertInstanceOf(\Support\Events\Log\Context\Whitelisted::class, $log->context);
    }

    #[Test]
    public function it_guards_type(): void
    {
        $this->expectException(MassAssignmentException::class);

        Model::preventSilentlyDiscardingAttributes();
        Log::make()->fill([
            'type' => 'article.updated',
        ]);
    }

    #[Test]
    public function it_guards_data(): void
    {
        $this->expectException(MassAssignmentException::class);

        Model::preventSilentlyDiscardingAttributes();
        Log::make()->fill([
            'data' => ['foo' => 'bar'],
        ]);
    }

    #[Test]
    public function it_guards_loggable(): void
    {
        $this->expectException(MassAssignmentException::class);

        Model::preventSilentlyDiscardingAttributes();
        Log::make()->fill([
            'loggable' => Article::factory()->create(),
        ]);
    }

    #[Test]
    public function it_guards_loggable_id(): void
    {
        $this->expectException(MassAssignmentException::class);

        Model::preventSilentlyDiscardingAttributes();
        Log::make()->fill([
            'loggable_id' => 'some-id',
        ]);
    }

    #[Test]
    public function it_guards_loggable_type(): void
    {
        $this->expectException(MassAssignmentException::class);

        Model::preventSilentlyDiscardingAttributes();
        Log::make()->fill([
            'loggable_type' => 'article',
        ]);
    }

    #[Test]
    public function it_throws_when_loggable_morph_is_unmapped(): void
    {
        $this->expectException(ClassMorphViolationException::class);

        Log::make()->forceFill([
            'loggable' => new NoMorphMap,
        ]);
    }

    #[Test]
    public function it_relates_to_loggable(): void
    {
        $event = new Updating($article = Article::factory()->create());

        $log = Log::create([
            'event' => $event,
            'context' => Context::getFacadeRoot(),
            'idempotency_key' => Str::uuid7()->toString(),
            'occurred_at' => now(),
        ]);

        $this->assertSame($article->getKey(), $log->loggable->getKey());
    }

    #[Test]
    public function it_unsets_loggable_appends_when_stored(): void
    {
        $event = new Updating($article = Article::factory()->create());

        $this->assertArrayHasKey('preview', $article->toArray());

        $log = Log::create([
            'event' => $event,
            'context' => Context::getFacadeRoot(),
            'idempotency_key' => Str::uuid7()->toString(),
            'occurred_at' => now(),
        ]);

        $event = $log->event;

        $this->assertArrayNotHasKey('preview', $log->event->{$event->loggableProperty}->toArray());
    }

    #[Test]
    public function it_unsets_loggable_relations_when_stored(): void
    {
        $event = new Updating(Article::factory()->create());

        $article = Article::with('category')->first();
        $this->assertArrayHasKey('category', $article->toArray());

        $log = Log::create([
            'event' => $event,
            'context' => Context::getFacadeRoot(),
            'idempotency_key' => Str::uuid7()->toString(),
            'occurred_at' => now(),
        ]);

        $event = $log->event;

        $this->assertArrayNotHasKey('category', $log->event->{$event->loggableProperty}->toArray());
    }

    #[Test]
    public function it_records_data_from_array_to_loggable(): void
    {
        $log = Log::make()->fill([
            'event' => new Updated(Article::factory()->create()),
        ]);

        $this->assertSame($log->event->loggable->toLoggable(), $log->data);
    }

    #[Test]
    public function it_records_data_from_arrayable_to_loggable(): void
    {
        $log = Log::make()->fill([
            'event' => new \Tests\Fixtures\Support\Entities\Tags\Events\Updated(Tag::factory()->create()),
        ]);

        $this->assertSame($log->event->loggable->toLoggable()->toArray(), $log->data);
    }

    #[Test]
    public function it_records_data_from_json_serializable_to_loggable(): void
    {
        $log = Log::make()->fill([
            'event' => new \Tests\Fixtures\Support\Entities\Categories\Events\Updated(Category::factory()->create()),
        ]);

        $this->assertSame($log->event->loggable->toLoggable()->jsonSerialize(), $log->data);
    }

    #[Test]
    public function it_records_data_from_jsonable_to_loggable(): void
    {
        $log = Log::make()->fill([
            'event' => new \Tests\Fixtures\Support\Entities\Comments\Events\Updated(Comment::factory()->create()),
        ]);

        $this->assertSame(json_decode($log->event->loggable->toLoggable()->toJson(), true), $log->data);
    }

    #[Test]
    public function it_records_data_from_iterable_to_loggable(): void
    {
        $log = Log::make()->fill([
            'event' => new \Tests\Fixtures\Support\Entities\Notes\Events\Updated(Note::factory()->create()),
        ]);

        /** @var iterable<string, mixed> $iterable */
        $iterable = $log->event->loggable->toLoggable();

        $this->assertSame(iterator_to_array($iterable), $log->data);
    }
}
