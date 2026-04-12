<?php

declare(strict_types=1);

namespace Support\Events\Log\Concerns;

use Illuminate\Database\Eloquent\Model;
use PHPUnit\Framework\Attributes\CoversTrait;
use PHPUnit\Framework\Attributes\Test;
use ReflectionClass;
use Support\Events\Log\Attributes\Alias;
use Support\Events\Log\Attributes\Exceptions\AliasMissing;
use Support\Events\Log\Contracts\Recordable;
use Support\Events\Log\Contracts\RecordableTestCases;
use Tests\Fixtures\Support\Entities\Articles\Article;
use Tests\Fixtures\Support\Entities\Articles\Events\Created;
use Tests\Fixtures\Tooling\EventLog\RecordableWithoutAlias;
use Tests\Support\Events\Log\Contracts\TestsRecordable;
use Tests\TestCase;

/**
 * @mixin \PHPUnit\Framework\TestCase
 */
#[CoversTrait(SupportsEventLog::class)]
class SupportsEventLogTest extends TestCase implements TestsRecordable
{
    use RecordableTestCases;

    public Recordable $event {
        get => new Created(Article::factory()->make());
    }

    #[Test]
    public function it_uses_the_supports_event_log_trait(): void
    {
        $this->assertContains(
            SupportsEventLog::class,
            collect(
                new ReflectionClass(Created::class)->getTraits()
            )->keys(),
        );
    }

    #[Test]
    public function it_exposes_the_entity(): void
    {
        $event = new Created(Article::factory()->make());

        $this->assertInstanceOf(Model::class, $event->entity);
    }

    #[Test]
    public function it_derives_alias(): void
    {
        $event = new Created(Article::factory()->make());

        $expected = (new ReflectionClass($event))
            ->getAttributes(Alias::class)[0]
            ->newInstance()
            ->name;

        $this->assertSame($expected, $event->alias->toString());
    }

    #[Test]
    public function it_derives_unique_alias_by_interpolating_entity_id(): void
    {
        $article = Article::factory()->make();
        $event = new Created($article);

        $this->assertSame(
            "article.{$article->id}.created",
            $event->uniqueAlias->toString(),
        );
    }

    #[Test]
    public function it_throws_when_alias_attribute_is_missing(): void
    {
        $article = Article::factory()->make();
        $event = new RecordableWithoutAlias($article);

        $this->expectException(AliasMissing::class);

        $event->alias; // @phpstan-ignore expr.resultUnused
    }
}
